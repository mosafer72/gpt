<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::with(['currentWorkspace', 'workspaces']);

        // جستجو بر اساس نام یا شماره تلفن
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
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
     * Get user workspaces
     */
    public function workspaces(User $user)
    {
        return response()->json([
            'status' => 'success',
            'data' => $user->workspaces
        ]);
    }

    /**
     * Assign workspace to user
     */
    public function assignWorkspace(Request $request, User $user)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id'
        ]);

        $workspace = Workspace::findOrFail($validated['workspace_id']);
        $workspace->update(['user_id' => $user->id]);

        return response()->json([
            'status' => 'success',
            'message' => 'فضا کاری با موفقیت به کاربر اختصاص یافت.'
        ]);
    }
}
