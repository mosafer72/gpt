<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LlmRequest;
use Illuminate\Http\Request;

class LlmRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = LlmRequest::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('provider')) {
            $query->where('provider', $request->provider);
        }

        return response()->json($query->orderBy('id', 'desc')->paginate(20));
    }

    public function show($id)
    {
        return response()->json(LlmRequest::findOrFail($id));
    }
}
