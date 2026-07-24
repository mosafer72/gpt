<?php

namespace Database\Seeders;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class ChatWithMessagesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        $workspace = Workspace::first();


        if (!$user || !$workspace) {

            $this->command->error(
                "User or Workspace not found"
            );

            return;

        }


        /*
        |--------------------------------------------------------------------------
        | Main Chat
        |--------------------------------------------------------------------------
        */

        $chat = Chat::create([

            'user_id' => $user->id,

            'workspace_id' => $workspace->id,

            'title' => 'چت طولانی برای تست صفحه‌بندی',

        ]);



        /*
        |--------------------------------------------------------------------------
        | 200 Messages
        |--------------------------------------------------------------------------
        */

        for ($i = 0; $i < 200; $i++) {

            Message::create([

                'chat_id' => $chat->id,

                'role' => $i % 2 === 0
                    ? 'user'
                    : 'assistant',

                'content' =>
                    $this->generateLongMessage(
                        $i
                    ),

            ]);

        }



        /*
        |--------------------------------------------------------------------------
        | More Chats
        |--------------------------------------------------------------------------
        */

        Chat::factory()
            ->count(5)
            ->create([

                'user_id' => $user->id,

                'workspace_id' => $workspace->id,

            ])
            ->each(function ($chat) {


                $messageCount = rand(10, 30);


                for ($i = 0; $i < $messageCount; $i++) {


                    Message::create([

                        'chat_id' => $chat->id,

                        'role' => $i % 2 === 0
                            ? 'user'
                            : 'assistant',

                        'content' =>
                            $this->generateMediumMessage(
                                $i
                            ),

                    ]);

                }

            });

    }



    private function generateLongMessage(int $index): string
    {
        $texts = [

            'لورم ایپسوم متن ساختگی برای تست سیستم چت و صفحه بندی پیام ها است.',

            'یادگیری ماشین و هوش مصنوعی در سال های اخیر رشد زیادی داشته است.',

            'لاراول یک فریم ورک قدرتمند برای توسعه برنامه های وب است.',

            'این پیام برای بررسی عملکرد ذخیره سازی و نمایش پیام ها ایجاد شده است.',

            'سیستم Workspace امکان مدیریت چند محیط کاری مستقل را فراهم می کند.',

        ];


        return $texts[$index % count($texts)]
            . ' (پیام شماره '
            . ($index + 1)
            . ')';
    }



    private function generateMediumMessage(int $index): string
    {
        $texts = [

            'این یک پیام آزمایشی است.',

            'چگونه می‌توانم کمک کنم؟',

            'پاسخ آماده شد.',

            'لطفاً سوال خود را ارسال کنید.',

            'ادامه بده.',

        ];


        return $texts[$index % count($texts)]
            . ' ('
            . ($index + 1)
            . ')';
    }
}
