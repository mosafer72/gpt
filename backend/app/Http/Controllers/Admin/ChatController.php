<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index()
    {
        return response()->json(Chat::with('user')->withCount('messages')->orderBy('id', 'desc')->paginate(20));
    }

    public function show($id)
    {
        return response()->json(Chat::with(['user', 'messages'])->findOrFail($id));
    }

    public function destroy($id)
    {
        Chat::findOrFail($id)->delete();
        return response()->json(['message' => 'Chat deleted']);
    }
}
