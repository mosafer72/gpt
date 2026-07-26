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
        $types = WorkspaceType::latest()->get();
        return response()->json($types);
    }

    /**
     * ایجاد یک نوع فضای کاری جدید
     */
    public function store(Request $request): JsonResponse
    {
        // اعتبارسنجی بر اساس فیلدهای fillable مدل WorkspaceType[cite: 5]
        $validated = $request->validate([
            'key' => 'required|string|unique:workspace_types,key',
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $workspaceType = WorkspaceType::create($validated);

        return response()->json([
            'message' => 'نوع فضای کاری با موفقیت ایجاد شد.',
            'workspace_type' => $workspaceType
        ], 201);
    }

    /**
     * ویرایش اطلاعات نوع فضای کاری
     */
    public function update(Request $request, $id): JsonResponse
    {
        $workspaceType = WorkspaceType::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|unique:workspace_types,key,' . $workspaceType->id,
            'name' => 'required|string|max:255',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
        ]);

        $workspaceType->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'workspace_type' => $workspaceType
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال (بر اساس فیلد کست شده is_active[cite: 5])
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
