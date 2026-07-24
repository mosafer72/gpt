<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use Illuminate\Http\Request;

class AiModelController extends Controller
{
    public function index()
    {
        return response()->json(AiModel::with('provider')->get());
    }

     public function show($id)
    {
        $model = AiModel::findOrFail($id);
        return response()->json($model);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ai_models',
            'name' => 'required|string',
            'model_name' => 'required|string',
            'provider_id' => 'required|exists:llm_providers,id',
            'input_cost' => 'sometimes|numeric',
            'output_cost' => 'sometimes|numeric',
            'is_active' => 'boolean',
        ]);

        return response()->json(AiModel::create($validated), 201);
    }

    public function update(Request $request, $id)
    {
        $model = AiModel::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'model_name' => 'sometimes|string',
            'provider_id' => 'sometimes|exists:llm_providers,id',
            'input_cost' => 'sometimes|numeric',
            'output_cost' => 'sometimes|numeric',
            'is_active' => 'boolean',
        ]);

        $model->update($validated);
        return response()->json($model);
    }

    public function destroy($id)
    {
        AiModel::findOrFail($id)->delete();
        return response()->json(['message' => 'Model deleted']);
    }
}
