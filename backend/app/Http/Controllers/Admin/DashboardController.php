<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Chat;
use App\Models\Message;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\AiModel;
use App\Models\Subscription;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return response()->json([
            'users' => User::count(),
            'chats' => Chat::count(),
            'messages' => Message::count(),
            'revenue' => PaymentTransaction::where('status', 'paid')->sum('amount'),
            'transactions' => PaymentTransaction::count(),
            'plans' => Plan::count(),
            'models' => AiModel::where('is_active', true)->count(),
            'todayMessages' => Message::whereDate('created_at', today())->count(),
            'weekMessages' => Message::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'monthMessages' => Message::whereMonth('created_at', now()->month)->count(),
            'activeSubscriptions' => Workspace::where('is_active', true)->count(),
            'pendingTransactions' => PaymentTransaction::where('status', 'pending')->count(),
        ]);
    }
}
