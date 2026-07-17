<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Memory;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Memory::with('user');

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->orderBy('id', 'desc')->paginate(20));
    }

    public function destroy($id)
    {
        Memory::findOrFail($id)->delete();
        return response()->json(['message' => 'Memory deleted']);
    }
}
