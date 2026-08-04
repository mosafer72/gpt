<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceType;
use App\Models\Usage;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // بارگذاری ایمن و استاندارد تمام روابط مورد نیاز
        $workspaces = $user->workspaces()
            ->where('is_active', true)
            ->with([
                'workspaceType',
                'tokenWallet',
                'workspaceSubscriptions' => function ($query) {
                    $query->where('is_active', true)
                          ->where('expires_at', '>', now())
                          ->with('workspacePlan.models.provider');
                }
            ])
            ->get();

        $workspaceIds = $workspaces->pluck('id');

        $usage = Usage::whereIn('workspace_id', $workspaceIds)
            ->whereDate('created_at', today())
            ->select('workspace_id')
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw('COALESCE(SUM(input_tokens),0) as input_tokens')
            ->selectRaw('COALESCE(SUM(output_tokens),0) as output_tokens')
            ->selectRaw('COALESCE(SUM(total_tokens),0) as total_tokens')
            ->selectRaw('COALESCE(SUM(total_cost),0) as total_cost')
            ->groupBy('workspace_id')
            ->get()
            ->keyBy('workspace_id');

        // بارگذاری تایپ‌ها به همراه پلن‌ها و مدل‌ها با تفکیک ساختاری
        $workspaceTypes = WorkspaceType::where('is_active', true)
            ->with(['plans' => function($q) {
                $q->where('is_active', true);
            }, 'plans.models.provider'])
            ->get()
            ->map(function ($type) {
                return [
                    'id' => $type->id,
                    'key' => $type->key, // مثلا 'student' یا 'personal'
                    'display_name' => $type->display_name,
                    'icon' => $type->icon,
                    'features' => $type->features,
                    'plans' => $type->plans->map(function ($plan) use ($type) {
                        return [
                            'id' => $plan->id,
                            // ترکیب کلید والد با نام پلن برای جلوگیری از تداخل در فرانت‌اند (مثلا student-basic)
                            'unique_key' => $type->key . '-' . $plan->name,
                            'name' => $plan->name,
                            'display_name' => $plan->display_name,
                            'price' => $plan->price,
                            'duration_days' => $plan->duration_days,
                            'token_limit' => $plan->token_limit,
                            'capabilities' => $plan->capabilities,
                            'models' => $plan->models->map(fn($model) => [
                                'id' => $model->id,
                                'name' => $model->name,
                                'key' => $model->key,
                                'type' => $model->type,
                                'provider' => $model->provider ? [
                                    'id' => $model->provider->id,
                                    'name' => $model->provider->name,
                                ] : null,
                            ])->values(),
                        ];
                    })->values(),
                ];
            });

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
            ],

            'workspaces' => $workspaces->map(function ($workspace) use ($usage) {
                $subscription = $workspace->workspaceSubscriptions->first();
                $plan = $subscription?->workspacePlan;
                $workspaceType = $workspace->workspaceType;
                $today = $usage[$workspace->id] ?? null;

                return [
                    'id' => $workspace->id,
                    'name' => $workspace->name,
                    'type' => $workspace->type,

                    'workspace_type' => $workspaceType ? [
                        'id' => $workspaceType->id,
                        'key' => $workspaceType->key,
                        'display_name' => $workspaceType->display_name,
                        'icon' => $workspaceType->icon,
                        'features' => $workspaceType->features,
                    ] : null,

                    'started_at' => $workspace->started_at,
                    'expires_at' => $workspace->expires_at,
                    'is_active' => $workspace->is_active,

                    'plan' => $plan ? [
                        'id' => $plan->id,
                        'unique_key' => $workspaceType ? ($workspaceType->key . '-' . $plan->name) : $plan->name,
                        'name' => $plan->name,
                        'display_name' => $plan->display_name,
                        'price' => $plan->price,
                        'duration_days' => $plan->duration_days,
                        'token_limit' => $plan->token_limit,
                        'capabilities' => $plan->capabilities,

                        'models' => $plan->models->map(fn($model) => [
                            'id' => $model->id,
                            'name' => $model->name,
                            'key' => $model->key,
                            'type' => $model->type,
                            'provider' => $model->provider ? [
                                'id' => $model->provider->id,
                                'name' => $model->provider->name,
                            ] : null,
                        ])->values(),
                    ] : null,

                    'subscription' => $subscription ? [
                        'id' => $subscription->id,
                        'started_at' => $subscription->started_at,
                        'expires_at' => $subscription->expires_at,
                        'is_active' => $subscription->is_active,
                    ] : null,

                    'wallet' => [
                        'total_tokens' => $workspace->tokenWallet?->total_tokens ?? 0,
                        'used_tokens' => $workspace->tokenWallet?->used_tokens ?? 0,
                        'remaining_tokens' => $workspace->tokenWallet?->remaining_tokens ?? 0,
                    ],

                    'today_usage' => [
                        'requests' => (int) ($today->requests ?? 0),
                        'input_tokens' => (int) ($today->input_tokens ?? 0),
                        'output_tokens' => (int) ($today->output_tokens ?? 0),
                        'total_tokens' => (int) ($today->total_tokens ?? 0),
                        'total_cost' => (float) ($today->total_cost ?? 0),
                    ],
                ];
            })->values(),

            'workspace_types' => $workspaceTypes,
        ]);
    }
}
