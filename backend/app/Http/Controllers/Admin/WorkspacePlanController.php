<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkspacePlan;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkspacePlanController extends Controller
{
    /**
     * دریافت لیست تمامی طرح‌های فضای کاری همراه با روابط
     */
    public function index(): JsonResponse
    {
        $plans = WorkspacePlan::with(['workspaceType', 'models'])->latest()->get();
        return response()->json($plans);
    }

    /**
     * ایجاد طرح جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'workspace_type_id' => 'required|exists:workspace_types,id',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'duration_days' => 'required|integer',
            'token_limit' => 'nullable|integer',
            'features' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'exists:ai_models,id',
        ]);

        $plan = WorkspacePlan::create($validated);

        if (isset($validated['model_ids'])) {
            $plan->models()->sync($validated['model_ids']);
        }

        return response()->json([
            'message' => 'طرح فضای کاری با موفقیت ایجاد شد.',
            'workspace_plan' => $plan->load(['workspaceType', 'models'])
        ], 201);
    }

    /**
     * بروزرسانی طرح
     */
    public function update(Request $request, $id): JsonResponse
    {
        $plan = WorkspacePlan::findOrFail($id);

        $validated = $request->validate([
            'workspace_type_id' => 'required|exists:workspace_types,id',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'duration_days' => 'required|integer',
            'token_limit' => 'nullable|integer',
            'features' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'exists:ai_models,id',
        ]);

        $plan->update($validated);

        if (isset($validated['model_ids'])) {
            $plan->models()->sync($validated['model_ids']);
        } else {
            $plan->models()->detach();
        }

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'workspace_plan' => $plan->load(['workspaceType', 'models'])
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $plan = WorkspacePlan::findOrFail($id);

        $plan->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'وضعیت با موفقیت تغییر کرد.'
        ]);
    }
}
