<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Database\Seeder;

class ChatWithMessagesSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد یک چت با عنوان خاص
        $chat = Chat::create([
            'title' => 'چت طولانی برای تست صفحه‌بندی',
        ]);

        // تولید ۲۰۰ پیام برای این چت (۱۰۰ کاربر و ۱۰۰ دستیار)
        for ($i = 0; $i < 200; $i++) {
            $role = $i % 2 === 0 ? 'user' : 'assistant';
            Message::create([
                'chat_id' => $chat->id,
                'role' => $role,
                'content' => $this->generateLongMessage($role, $i),
            ]);
        }

        // همچنین چند چت دیگر با تعداد پیام متوسط
        Chat::factory()->count(5)->create()->each(function ($chat) {
            $messageCount = rand(10, 30);
            for ($i = 0; $i < $messageCount; $i++) {
                $role = $i % 2 === 0 ? 'user' : 'assistant';
                Message::create([
                    'chat_id' => $chat->id,
                    'role' => $role,
                    'content' => $this->generateMediumMessage($role, $i),
                ]);
            }
        });
    }

    private function generateLongMessage(string $role, int $index): string
    {
        $longTexts = [
            'لورم ایپسوم متن ساختگی با تولید سادگی نامفهوم از صنعت چاپ و با استفاده از طراحان گرافیک است. چاپگرها و متون بلکه روزنامه و مجله در ستون و سطرآنچنان که لازم است و برای شرایط فعلی تکنولوژی مورد نیاز و کاربردهای متنوع با هدف بهبود ابزارهای کاربردی می‌باشد.',
            'کتاب‌های زیادی در شصت و سه درصد گذشته، حال و آینده شناخت فراوان جامعه و متخصصان را می‌طلبد تا با نرم‌افزارها شناخت بیشتری را برای طراحان رایانه‌ای علی‌الخصوص طراحان خلاقی و فرهنگ پیشرو در زبان فارسی ایجاد کرد.',
            'در این صورت می‌توان امید داشت که تمام و دشواری موجود در ارائه راهکارها و شرایط سخت تایپ به پایان رسد و زمان مورد نیاز شامل حروفچینی دستاوردهای اصلی و جوابگوی سوالات پیوسته اهل دنیای موجود طراحی اساسا مورد استفاده قرار گیرد.',
            'با توجه به اهمیت یادگیری ماشین در دنیای امروز، بسیاری از شرکت‌ها به دنبال استفاده از این تکنولوژی برای بهبود فرآیندهای خود هستند و این موضوع باعث شده است که بازار کار برای متخصصان این حوزه بسیار داغ باشد.',
            'لاراول یکی از محبوب‌ترین فریم‌ورک‌های PHP است که با ارائه امکانات قدرتمند مانند Eloquent ORM، سیستم Blade و ابزارهای امنیتی، توسعه برنامه‌های وب را بسیار سریع و آسان کرده است.',
        ];

        return $longTexts[$index % count($longTexts)] . ' (پیام شماره ' . ($index + 1) . ')';
    }

    private function generateMediumMessage(string $role, int $index): string
    {
        $shortTexts = [
            'این یک پیام آزمایشی است.',
            'چگونه می‌توانم به شما کمک کنم؟',
            'لطفاً سوال خود را مطرح کنید.',
            'پاسخ به سوال شما در حال آماده‌سازی است.',
            'آیا سوال دیگری دارید؟',
        ];

        return $shortTexts[$index % count($shortTexts)] . ' (' . ($index + 1) . ')';
    }
}
