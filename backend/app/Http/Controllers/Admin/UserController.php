<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::with(['currentSubscription.plan'])->get());
    }

    public function show($id)
    {
        return response()->json(User::with(['chats.messages', 'subscription.plan', 'memories'])->findOrFail($id));
    }

    public function destroy($id)
    {
        User::findOrFail($id)->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
