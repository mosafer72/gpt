<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmProvider;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderController extends Controller
{
    /**
     * نمایش لیست همه ارائه‌دهندگان
     */
    public function index()
    {
        $providers = LlmProvider::orderBy('id', 'desc')->get();
        return response()->json($providers);
    }

    /**
     * ذخیره یک ارائه‌دهنده جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:llm_providers,key',
            'name' => 'required|string|max:100',
            'api_key' => 'required|string|max:255',
            'base_url' => 'nullable|string|max:255|url',
            'is_active' => 'boolean',
        ]);

        $provider = LlmProvider::create([
            'key' => $validated['key'],
            'name' => $validated['name'],
            'api_key' => encrypt($validated['api_key']), // رمزنگاری کلید API
            'base_url' => $validated['base_url'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json($provider, 201);
    }

    /**
     * نمایش یک ارائه‌دهنده خاص
     */
    public function show($id)
    {
        return $id;
        $provider = LlmProvider::findOrFail($id);
        // کلید API را رمزگشایی می‌کنیم تا در صورت نیاز نمایش داده شود
        $provider->api_key = decrypt($provider->api_key);
        return response()->json($provider);
    }

    /**
     * به‌روزرسانی یک ارائه‌دهنده
     */
    public function update(Request $request, $id)
    {
        $provider = LlmProvider::findOrFail($id);

        $validated = $request->validate([
            'key' => ['sometimes', 'string', 'max:50', Rule::unique('llm_providers')->ignore($id)],
            'name' => 'sometimes|string|max:100',
            'api_key' => 'nullable|string|max:255',
            'base_url' => 'nullable|string|max:255|url',
            'is_active' => 'boolean',
        ]);

        $data = [];

        if (isset($validated['key'])) {
            $data['key'] = $validated['key'];
        }

        if (isset($validated['name'])) {
            $data['name'] = $validated['name'];
        }

        if (array_key_exists('api_key', $validated) && !empty($validated['api_key'])) {
            $data['api_key'] = encrypt($validated['api_key']);
        }

        if (isset($validated['base_url'])) {
            $data['base_url'] = $validated['base_url'];
        }

        if (isset($validated['is_active'])) {
            $data['is_active'] = $validated['is_active'];
        }

        $provider->update($data);

        return response()->json($provider);
    }

    /**
     * حذف یک ارائه‌دهنده
     */
    public function destroy($id)
    {
        $provider = LlmProvider::findOrFail($id);

        // بررسی اینکه آیا مدل‌هایی به این ارائه‌دهنده متصل هستند
        if ($provider->models()->count() > 0) {
            return response()->json([
                'message' => 'این ارائه‌دهنده دارای مدل‌های متصل است. ابتدا مدل‌ها را حذف یا تغییر دهید.'
            ], 422);
        }

        $provider->delete();
        return response()->json(['message' => 'ارائه‌دهنده با موفقیت حذف شد']);
    }
}
