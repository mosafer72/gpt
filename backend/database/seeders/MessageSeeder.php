<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Seeder;

class MessageSeeder extends Seeder
{
    public function run(): void
    {
        // برای هر چت، بین ۵ تا ۲۰ پیام تولید کن
        Chat::all()->each(function ($chat) {
            $messageCount = rand(5, 20);

            // ایجاد پیام‌های متناوب کاربر و دستیار
            for ($i = 0; $i < $messageCount; $i++) {
                $role = $i % 2 === 0 ? 'user' : 'assistant';
                Message::create([
                    'chat_id' => $chat->id,
                    'role' => $role,
                    'content' => $this->generateMessage($role, $i),
                ]);
            }
        });
    }

    private function generateMessage(string $role, int $index): string
    {
        $userMessages = [
            'سلام، چطور می‌توانم به شما کمک کنم؟',
            'در مورد هوش مصنوعی توضیح دهید.',
            'تفاوت بین یادگیری نظارت‌شده و بدون نظارت چیست؟',
            'لطفاً یک کد پایتون برای مرتب‌سازی بنویسید.',
            'بهترین کتاب برای یادگیری PHP کدام است؟',
            'چگونه می‌توانم یک وب‌سایت با لاراول بسازم؟',
            'در مورد API توضیح دهید.',
            'آیا می‌توانید یک نمونه پروژه معرفی کنید؟',
            'چگونه خطاهای رایج در لاراول را رفع کنم؟',
            'آینده‌ی برنامه‌نویسی وب چگونه خواهد بود؟',
        ];

        $assistantMessages = [
            'سلام! خوشحالم که می‌توانم کمک کنم. چه سوالی دارید؟',
            'هوش مصنوعی شاخه‌ای از علوم کامپیوتر است که به ساخت ماشین‌های هوشمند می‌پردازد.',
            'یادگیری نظارت‌شده با داده‌های برچسب‌دار کار می‌کند، در حالی که بدون نظارت از داده‌های بدون برچسب استفاده می‌کند.',
            'کد مرتب‌سازی به این شکل است: `def sort_list(arr): return sorted(arr)`',
            'کتاب "PHP & MySQL: Novice to Ninja" یک منبع عالی است.',
            'برای شروع با لاراول، می‌توانید از مستندات رسمی و دوره‌های آموزشی استفاده کنید.',
            'API مخفف Application Programming Interface است و امکان ارتباط بین نرم‌افزارها را فراهم می‌کند.',
            'یک نمونه پروژه‌ی عالی، سیستم مدیریت محتوای ساده با لاراول است.',
            'برای رفع خطاها، ابتدا لاگ‌ها را بررسی کنید و سپس از ابزارهای دیباگ استفاده کنید.',
            'آینده‌ی وب به سمت استفاده بیشتر از هوش مصنوعی و برنامه‌های واکنش‌گرا پیش می‌رود.',
        ];

        if ($role === 'user') {
            return $userMessages[$index % count($userMessages)];
        }

        return $assistantMessages[$index % count($assistantMessages)];
    }
}
