<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AiModelController extends Controller
{
    /**
     * دریافت لیست تمامی مدل‌های هوش مصنوعی همراه با ارائه‌دهنده
     */
    public function index(): JsonResponse
    {
        $models = AiModel::with('provider')->latest()->get();
        return response()->json($models);
    }

    /**
     * ایجاد مدل جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ai_models,key',
            'name' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'provider_id' => 'required|exists:llm_providers,id',
            'input_cost' => 'nullable|numeric',
            'output_cost' => 'nullable|numeric',
            'type' => 'nullable|string|max:255',
        ]);

        $aiModel = AiModel::create($validated);

        return response()->json([
            'message' => 'مدل هوش مصنوعی با موفقیت ایجاد شد.',
            'ai_model' => $aiModel->load('provider')
        ], 201);
    }

    /**
     * بروزرسانی مدل
     */
    public function update(Request $request, $id): JsonResponse
    {
        $aiModel = AiModel::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|unique:ai_models,key,' . $aiModel->id,
            'name' => 'required|string|max:255',
            'model_name' => 'required|string|max:255',
            'provider_id' => 'required|exists:llm_providers,id',
            'input_cost' => 'nullable|numeric',
            'output_cost' => 'nullable|numeric',
            'type' => 'nullable|string|max:255',
        ]);

        $aiModel->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'ai_model' => $aiModel->load('provider')
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $aiModel = AiModel::findOrFail($id);

        $aiModel->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'وضعیت با موفقیت تغییر کرد.'
        ]);
    }
}
