<?php

namespace App\Application\Prompts;

use App\Models\Workspace;

class WorkspacePromptResolver
{
    /**
     * نگاشت نوع ورک‌اسپیس به کلاس پرامپت مربوطه
     */
    protected static array $map = [
        'student' => StudentSystemPrompt::class,
        'chat' => SystemPrompt::class,
    ];

    public static function resolve(Workspace $workspace): string
    {


        // ۱. اولویت اول: اگر کاربر در دیتابیس پرامپت سفارشی ثبت کرده بود
        if (!empty($workspace->system_prompt)) {
            return $workspace->system_prompt;
        }

        // ۲. اولویت دوم: انتخاب کلاس پرامپت بر اساس تایپ ورک‌اسپیس (مثلاً $workspace->type)
        $type = $workspace->type ?? 'document';

        if (isset(self::$map[$type])) {
            $promptClass = self::$map[$type];
            return $promptClass::build();
        }

        // ۳. اولویت سوم: فال‌بک به پرامپت پیش‌فرض اسناد
        return SystemPrompt::build();
    }
}
