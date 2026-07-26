<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmProvider;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LlmProviderController extends Controller
{
    /**
     * دریافت لیست تمامی ارائه‌دهندگان هوش مصنوعی
     */
    public function index(): JsonResponse
    {
        $providers = LlmProvider::latest()->get();
        return response()->json($providers);
    }

    /**
     * ایجاد ارائه‌دهنده جدید
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:llm_providers,key',
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|string',
        ]);

        $provider = LlmProvider::create($validated);

        return response()->json([
            'message' => 'ارائه‌دهنده با موفقیت ایجاد شد.',
            'llm_provider' => $provider
        ], 201);
    }

    /**
     * بروزرسانی ارائه‌دهنده
     */
    public function update(Request $request, $id): JsonResponse
    {
        $provider = LlmProvider::findOrFail($id);

        $validated = $request->validate([
            'key' => 'required|string|unique:llm_providers,key,' . $provider->id,
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'base_url' => 'nullable|string',
        ]);

        $provider->update($validated);

        return response()->json([
            'message' => 'اطلاعات با موفقیت بروزرسانی شد.',
            'llm_provider' => $provider
        ]);
    }

    /**
     * تغییر وضعیت فعال/غیرفعال
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        $validated = $request->validate(['is_active' => 'required|boolean']);
        $provider = LlmProvider::findOrFail($id);

        $provider->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'message' => 'وضعیت با موفقیت تغییر کرد.'
        ]);
    }
}
