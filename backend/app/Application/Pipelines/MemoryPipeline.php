<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Domain\Contracts\MemoryRetriever;
use App\Domain\Contracts\MemoryStore;
use App\Domain\Memory\DTOs\MemoryDTO;

class MemoryPipeline
{

    public function __construct(
        protected MemoryRetriever $retriever,
        protected MemoryStore $store
    ) {
    }


    /**
     * ذخیره Memory از طریق Queue Job
     */
    public function storeMessage(
        string $userId,
        string $message
    ): void {


        /**
         * استخراج اطلاعات مهم از پیام
         */
        $memoryData = $this->extractMemory(
            $message
        );


        /**
         * اگر چیزی برای ذخیره پیدا نشد
         */
        if (!$memoryData) {

            return;

        }



        $memory = new MemoryDTO(

            id: uniqid(),

            user_id: $userId,

            /**
             * مقدار استخراج شده
             *
             * مثال:
             * "برنامه نویس لاراول"
             */
            text: $memoryData['text'],


            importance:
            $memoryData['importance'],


            category:
            $memoryData['category'],


            /**
             * متن اصلی برای Trace
             */
            source_message:
            $message,


            embedding: null,


            last_used:
            now()->toDateTimeString(),


            created_at:
            now()->toDateTimeString(),

        );



        $this->store->store(
            $memory
        );

    }




    /**
     * دریافت حافظه مرتبط
     */
    public function retrieve(
        ChatContext $context
    ): ChatContext {


        $memories =
            $this->retrieveMemories(
                userId: $context->userId,
                query: $context->currentMessage
            );


        $context->setMemories(
            $memories
        );


        return $context;

    }





    /**
     * Retrieval اصلی
     */
    public function retrieveMemories(
        string $userId,
        string $query
    ): array {


        $results =
            $this->retriever->retrieve(
                userId: $userId,
                query: $query,
                limit: 10
            );



        return collect($results)
            ->map(function ($memory) use ($userId) {


                return new MemoryDTO(

                    id: uniqid(),

                    user_id: $userId,

                    text: $memory->text,

                    importance:
                    $memory->importance ?? 1,

                    category:
                    $memory->category ?? 'general',

                    source_message:
                    $memory->source_message ?? null,

                    embedding: null,

                    last_used:
                    now()->toDateTimeString(),

                    created_at:
                    now()->toDateTimeString(),

                );


            })
            ->toArray();

    }







    /**
     * استخراج Memory از متن کاربر
     *
     * نسخه MVP
     *
     * بعداً جایگزین:
     * LLM Memory Extractor
     */
    protected function extractMemory(
        string $message
    ): ?array {


        $patterns = [


            /**
             * مثال:
             * من هستم برنامه نویس لاراول
             */
            '/من هستم (.+)/u' => [

                'category' => 'profile'

            ],



            /**
             * مثال:
             * من رنگ آبی دوست دارم
             */
            '/من (.+) دوست دارم/u' => [

                'category' => 'preference'

            ],



            /**
             * مثال:
             * ترجیح میدم شب کار کنم
             */
            '/ترجیح میدم (.+)/u' => [

                'category' => 'preference'

            ],



            /**
             * مثال:
             * همیشه فارسی جواب بده
             */
            '/همیشه (.+)/u' => [

                'category' => 'instruction'

            ],


            /**
             * مثال:
             * یادم باشه من لاراول کار میکنم
             */
            '/یادم باشه (.+)/u' => [

                'category' => 'profile'

            ],


        ];




        foreach ($patterns as $pattern => $meta) {


            if (
                preg_match(
                    $pattern,
                    $message,
                    $matches
                )
            ) {


                return [

                    'text' =>
                    trim($matches[1]),


                    'category' =>
                    $meta['category'],


                    'importance' =>
                    8,

                ];

            }

        }



        return null;

    }

}
