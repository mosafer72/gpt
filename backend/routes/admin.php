<?php

use App\Http\Controllers\Admin\AiModelController;
use App\Http\Controllers\Admin\LlmProviderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WorkspacePlanController;
use App\Http\Controllers\Admin\WorkspaceSubscriptionController;
use App\Http\Controllers\Admin\WorkspaceTypeController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\WorkspaceController;

// ... سایر روت‌های شما

// روت‌های مربوط به پنل ادمین
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin']) // فرض بر این است که یک میدلور برای چک کردن دسترسی ادمین دارید
    ->group(function () {

        Route::get('/workspace-types', [WorkspaceTypeController::class, 'index']);
        Route::post('/workspace-types', [WorkspaceTypeController::class, 'store']);
        Route::put('/workspace-types/{id}', [WorkspaceTypeController::class, 'update']);
        Route::patch('/workspace-types/{id}/toggle-status', [WorkspaceTypeController::class, 'toggleStatus']);
        // مدیریت فضاهای کاری
        Route::get('/workspaces', [WorkspaceController::class, 'index']);
        Route::get('/workspaces/{id}', [WorkspaceController::class, 'show']);
        Route::patch('/workspaces/{id}/toggle-status', [WorkspaceController::class, 'toggleStatus']);

        Route::apiResource('llm-providers', LlmProviderController::class);
        Route::patch('llm-providers/{id}/toggle-status', [LlmProviderController::class, 'toggleStatus']);

        Route::apiResource('ai-models', AiModelController::class);
        Route::patch('ai-models/{id}/toggle-status', [AiModelController::class, 'toggleStatus']);

        Route::apiResource('workspace-plans', WorkspacePlanController::class);
        Route::patch('workspace-plans/{id}/toggle-status', [WorkspacePlanController::class, 'toggleStatus']);

        Route::apiResource('workspace-subscriptions', WorkspaceSubscriptionController::class);
        Route::patch('workspace-subscriptions/{id}/toggle-status', [WorkspaceSubscriptionController::class, 'toggleStatus']);

        Route::apiResource('users', UserController::class);

    // مسیرهای جدید کاربران
        Route::post('users/{user}/login-as', [UserController::class, 'loginAs']);
        Route::get('users/{user}/workspaces', [UserController::class, 'workspaces']);
        Route::post('users/{user}/assign-workspace', [UserController::class, 'assignWorkspace']);
    });
