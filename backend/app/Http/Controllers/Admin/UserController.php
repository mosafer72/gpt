<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspacePlan;
use App\Models\WorkspaceSubscription;
use App\Models\WorkspaceType;
use App\Models\TokenWallet;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with([
            'currentWorkspace.workspaceType',
            'workspaces.workspaceType',
            'workspaces.currentSubscription.workspacePlan',
            'workspaces.wallet'
        ]);

        // جستجو بر اساس نام یا شماره تلفن
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|unique:users,phone',
            'role' => 'required|in:admin,user',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'phone_verified_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'کاربر جدید با موفقیت ایجاد شد.',
            'data' => $user
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|unique:users,phone,' . $user->id,
            'role' => 'sometimes|in:admin,user',
        ]);

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'اطلاعات کاربر با موفقیت بروزرسانی شد.',
            'data' => $user
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'کاربر با موفقیت حذف شد.'
        ]);
    }

    /**
     * Login as user (Impersonate)
     */
    public function loginAs(User $user)
    {
        $token = $user->createToken('impersonate-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'token' => $token,
            'message' => 'توکن ورود به عنوان کاربر صادر شد.'
        ]);
    }

    /**
     * Get user workspaces with type and active subscription plan
     */
    public function workspaces(User $user)
    {
        $workspaces = $user->workspaces()->with([
            'workspaceType',
            'currentSubscription.workspacePlan',
            'wallet'
        ])->get();

        return response()->json([
            'status' => 'success',
            'data' => $workspaces
        ]);
    }

    /**
     * Get all active workspace types with their plans
     */
    public function getWorkspaceTypes()
    {
        $types = WorkspaceType::with('plans')->where('is_active', 1)->get();

        return response()->json([
            'status' => 'success',
            'data' => $types
        ]);
    }

    /**
     * Assign or create workspace with specific type and plan for user
     */
    public function assignWorkspace(Request $request, User $user)
    {
        $validated = $request->validate([
            'workspace_type_id' => 'required|exists:workspace_types,id',
            'workspace_plan_id' => 'required|exists:workspace_plans,id',
            'name' => 'nullable|string|max:255'
        ]);

        // بررسی اینکه آیا کاربر از قبل یک فضای کاری فعال از این نوع دارد یا خیر
        $existingActiveWorkspace = $user->workspaces()
            ->where('workspace_type_id', $validated['workspace_type_id'])
            ->where('is_active', true)
            ->first();

        if ($existingActiveWorkspace) {
            return response()->json([
                'status' => 'error',
                'message' => 'این کاربر در حال حاضر یک فضای کاری فعال از این نوع دارد.'
            ], 422);
        }

        $plan = WorkspacePlan::findOrFail($validated['workspace_plan_id']);
        $workspace_type=WorkspaceType::findOrFail($validated['workspace_type_id']);

        $workspace = Workspace::create([
            'user_id' => $user->id,
            'workspace_type_id' => $validated['workspace_type_id'],
            'workspace_plan_id' => $plan->id,
            'name' => $workspace_type->name,
            'type' => $workspace_type->name,
            'is_active' => true,
            'started_at' => now(),
            'expires_at' => now()->addDays($plan->duration_days ?? 30),
        ]);

        $subscriptionService = app(\App\Application\Services\WorkspaceSubscriptionService::class);
        $subscriptionService->upgrade($user, $workspace, $plan, 'admin_assignment');

        return response()->json([
            'status' => 'success',
            'message' => 'فضای کاری و پلن اشتراک با موفقیت برای کاربر ایجاد شد.',
            'data' => $workspace->load(['workspaceType', 'currentSubscription.workspacePlan', 'wallet'])
        ]);
    }

    /**
     * Update workspace token wallet (Add, Subtract, Set)
     */
    public function updateWallet(Request $request, User $user)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'amount' => 'required|integer',
            'action' => 'required|in:add,subtract,set'
        ]);

        // بررسی تعلق داشتن فضای کاری به کاربر مورد نظر
        $workspace = $user->workspaces()->where('id', $validated['workspace_id'])->firstOrFail();

        $wallet = TokenWallet::firstOrCreate([
            'workspace_id' => $workspace->id
        ], [
            'total_tokens' => 0,
            'used_tokens' => 0,
            'remaining_tokens' => 0
        ]);

        $amount = (int) $validated['amount'];

        if ($validated['action'] === 'add') {
            $wallet->total_tokens += $amount;
            $wallet->remaining_tokens += $amount;
        } elseif ($validated['action'] === 'subtract') {
            $wallet->remaining_tokens = max(0, $wallet->remaining_tokens - $amount);
        } elseif ($validated['action'] === 'set') {
            $wallet->total_tokens = $amount;
            $wallet->remaining_tokens = max(0, $amount - $wallet->used_tokens);
        }

        $wallet->save();

        return response()->json([
            'status' => 'success',
            'message' => 'کیف پول فضای کاری با موفقیت بروزرسانی شد.',
            'data' => $wallet
        ]);
    }

    // لغو اشتراک و غیرفعال کردن فضای کاری کاربر
    public function cancelWorkspaceSubscription(Request $request, User $user, Workspace $workspace)
    {
        // اطمینان از تعلق داشتن فضا به کاربر
        if ($workspace->user_id !== $user->id) {
            return response()->json(['status' => 'error', 'message' => 'دسترسی غیرمجاز'], 403);
        }

        $subscriptionService = app(\App\Application\Services\WorkspaceSubscriptionService::class);
        $subscriptionService->cancelCurrent($workspace);

        $workspace->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'اشتراک و فضای کاری با موفقیت غیرفعال شد.'
        ]);
    }
}
