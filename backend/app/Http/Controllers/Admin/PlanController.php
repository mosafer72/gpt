<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlanController extends Controller
{
    /**
     * نمایش لیست پلن‌ها با مدل‌های مرتبط
     */
    public function index()
    {
        $plans = Plan::with('models')->orderBy('id', 'desc')->get();
        return response()->json($plans);
    }

    /**
     * ایجاد پلن جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:plans,name',
            'display_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'daily_message_limit' => 'required|integer|min:0',
            'token_limit' => 'required|integer|min:0',
            'features' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'is_active' => 'boolean',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'exists:ai_models,id',
        ]);

        $plan = Plan::create($validated);

        // اتصال مدل‌ها
        if ($request->has('model_ids')) {
            $plan->models()->sync($request->model_ids);
        }

        return response()->json($plan->load('models'), 201);
    }

    /**
     * نمایش یک پلن با مدل‌های مرتبط
     */
    public function show($id)
    {
        // ✅ بارگذاری مدل‌های مرتبط
        $plan = Plan::with('models')->findOrFail($id);
        return response()->json($plan);
    }

    /**
     * به‌روزرسانی پلن
     */
    public function update(Request $request, $id)
    {
        $plan = Plan::findOrFail($id);

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:100',
            'price' => 'sometimes|numeric|min:0',
            'duration_days' => 'sometimes|integer|min:1',
            'daily_message_limit' => 'sometimes|integer|min:0',
            'token_limit' => 'sometimes|integer|min:0',
            'features' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'is_active' => 'boolean',
            'model_ids' => 'nullable|array',
            'model_ids.*' => 'exists:ai_models,id',
        ]);

        $plan->update($validated);

        // ✅ به‌روزرسانی مدل‌های مرتبط
        if ($request->has('model_ids')) {
            $plan->models()->sync($request->model_ids);
        }

        return response()->json($plan->load('models'));
    }

    /**
     * حذف پلن
     */
    public function destroy($id)
    {
        $plan = Plan::findOrFail($id);

        // حذف ارتباطات many-to-many
        $plan->models()->detach();
        $plan->delete();

        return response()->json(['message' => 'Plan deleted successfully']);
    }
}
