<?php

namespace App\Jobs;

use App\Domain\Contracts\MessageStore;
use App\Domain\Message\DTOs\MessageDTO;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;


class StoreMessageJob implements ShouldQueue
{

    use Queueable;


    public function __construct(
        public array $payload
    ) {
    }



    public function handle(
        MessageStore $store
    ): void {


        /**
         * ذخیره پیام در دیتابیس
         */
        $store->store(

            new MessageDTO(

                id:
                $this->payload['id'],

                conversation:
                (string) $this->payload['conversation'],

                user_id:
                $this->payload['user_id'] ?? null,

                role:
                $this->payload['role'],

                content:
                $this->payload['content'],

                token_count:
                $this->payload['token_count'] ?? 0,

                metadata:
                $this->payload['metadata'] ?? [],

                created_at:
                $this->payload['created_at']

            )

        );



        $conversationId =
            (string) $this->payload['conversation'];



        /**
         * پاک کردن کش تاریخچه LLM
         */
        Cache::forget(
            "conversation:{$conversationId}:history:8"
        );


        Cache::forget(
            "conversation:{$conversationId}:history:20"
        );



        /**
         * پاک کردن کش پیام‌های UI
         */
        $this->forgetMessageCache(
            $conversationId
        );




        /**
         * پاک کردن لیست چت‌ها
         */
        if (
            !empty($this->payload['user_id'])
        ) {

            Cache::forget(
                "user:{$this->payload['user_id']}:chats:last20"
            );

        }


    }




    protected function forgetMessageCache(
        string $conversationId
    ): void {


        $redis = Redis::connection('cache');


        $pattern =
            "laravel-cache-chat:{$conversationId}:messages:*";



        $cursor = null;


        do {


            $result = $redis->scan(
                $cursor,
                'MATCH',
                $pattern,
                'COUNT',
                100
            );


            foreach ($result[1] ?? [] as $key) {


                $redis->del($key);


            }



        } while ($cursor != 0);


    }

}
