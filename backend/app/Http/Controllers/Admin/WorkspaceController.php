<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WorkspaceController extends Controller
{
    /**
     * دریافت لیست فضاهای کاری برای پنل ادمین
     */
    public function index(): JsonResponse
    {
        // استفاده از Eager Loading برای جلوگیری از مشکل N+1
        // ارتباط‌های user و workspaceType که در مدل Workspace تعریف شده‌اند را لود می‌کنیم[cite: 3]
        $workspaces = Workspace::with(['user:id,name,phone', 'workspaceType:id,name,display_name'])
            ->latest()
            ->get();

        return response()->json($workspaces);
    }

    /**
     * تغییر وضعیت (فعال/غیرفعال) فضای کاری
     */
    public function toggleStatus(Request $request, $id): JsonResponse
    {
        // اعتبارسنجی ورودی
        $validated = $request->validate([
            'is_active' => 'required|boolean'
        ]);

        // پیدا کردن فضای کاری یا بازگرداندن خطای 404
        $workspace = Workspace::findOrFail($id);

        // آپدیت کردن فیلد is_active که در آرایه fillable مدل قرار دارد[cite: 3]
        $workspace->update([
            'is_active' => $validated['is_active']
        ]);

        return response()->json([
            'message' => 'وضعیت فضای کاری با موفقیت تغییر کرد.',
            'workspace' => $workspace
        ]);
    }

    /**
     * نمایش اطلاعات یک فضای کاری خاص (اختیاری - برای صفحه جزئیات)
     */
    public function show($id): JsonResponse
    {
        $workspace = Workspace::with([
            'user',
            'workspaceType',
            'workspaceSubscriptions.workspacePlan',
            'wallet'
        ])->findOrFail($id); // لود کردن ریلیشن‌های تعریف شده در مدل[cite: 3]

        return response()->json($workspace);
    }
}
