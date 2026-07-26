<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceSubscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkspaceSubscriptionController extends Controller
{
    /**
     * دریافت لیست تمامی اشتراک‌ها همراه با روابط
     */
    public function index(): JsonResponse
    {
        $subscriptions = WorkspaceSubscription::with(['user', 'workspace', 'workspacePlan'])->latest()->get();
        return response()->json($subscriptions);
    }

    /**
     * ایجاد اشتراک جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'workspace_id' => 'required|exists:workspaces,id',
            'workspace_plan_id' => 'required|exists:workspace_plans,id',
            'started_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:started_at',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $subscription = WorkspaceSubscription::create($validated);

        return response()->json([
            'message' => 'اشتراک با موفقیت ایجاد شد.',
            'workspace_subscription' => $subscription->load(['user', 'workspace', 'workspacePlan'])
        ], 201);
    }

    /**
     * بروزرسانی اشتراک
     */
    public function update(Request $request, $id): JsonResponse
    {
        $subscription = WorkspaceSubscription::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'workspace_id' => 'required|exists:workspaces,id',
            'workspace_plan_id' => 'required|exists:workspace_plans,id',
            'started_at' => 'required|date',
            'expires_at' => 'required|date|after_or_equal:started_at',
            'transaction_id' => 'nullable|string|max:255',
        ]);

        $subscription->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'workspace_subscription' => $subscription->load(['user', 'workspace', 'workspacePlan'])
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $subscription = WorkspaceSubscription::findOrFail($id);

        $subscription->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'وضعیت اشتراک با موفقیت تغییر کرد.'
        ]);
    }
}
