<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkspaceTypeController extends Controller
{
    /**
     * دریافت لیست تمامی انواع فضاهای کاری
     */
    public function index(): JsonResponse
    {
        $types = WorkspaceType::with("plans")->latest()->get();
        return response()->json($types);
    }

    /**
     * ایجاد یک نوع فضای کاری جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:workspace_types,key',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.key' => 'required_with:features|string',
            'features.*.icon' => 'nullable|string',
            'features.*.title' => 'required_with:features|string|max:255',
            'features.*.desc' => 'nullable|string',
            'features.*.path' => 'nullable|string',
        ]);

        $workspaceType = WorkspaceType::create($validated);

        return response()->json([
            'message' => 'نوع فضای کاری با موفقیت ایجاد شد.',
            'workspace_type' => $workspaceType
        ], 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $workspaceType = WorkspaceType::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|unique:workspace_types,key,' . $workspaceType->id,
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'features' => 'nullable|array',
            'features.*.key' => 'required_with:features|string',
            'features.*.icon' => 'nullable|string',
            'features.*.title' => 'required_with:features|string|max:255',
            'features.*.desc' => 'nullable|string',
            'features.*.path' => 'nullable|string',
        ]);

        // اگر پکیج فرم خالی فرستاد، آرایه خالی ذخیره شود
        if (!isset($validated['features'])) {
            $validated['features'] = [];
        }

        $workspaceType->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'workspace_type' => $workspaceType
        ]);
    }


    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $workspaceType = WorkspaceType::findOrFail($id);

        $workspaceType->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'وضعیت با موفقیت تغییر کرد.'
        ]);
    }
}
