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
     * دریافت حافظه مرتبط
     */
    public function retrieve(
        ChatContext $context
    ): ChatContext {


        $memories = $this->retriever
            ->retrieve(
                userId: $context->userId,
                query: $context->currentMessage,
                limit: 10
            );
        $memories = collect($memories)
            ->map(function ($memory) use ($context) {
                return new MemoryDTO(

                    id: uniqid(),

                    user_id: $context->userId,

                    text: $memory->content,

                    importance: 1,

                    category: 'general',

                    source_message: null,

                    embedding: null,

                    last_used: now()->toDateTimeString(),

                    created_at: now()->toDateTimeString(),

                );

            })
            ->toArray();


        $context->setMemories($memories);


        return $context;

    }






    /**
     * ذخیره حافظه جدید
     */
    public function store(
        ChatContext $context
    ): ChatContext {


        /**
         * فعلاً خیلی ساده:
         * فقط پیام‌هایی که مهم هستند ذخیره می‌شوند
         */


        if (
            !$this->shouldRemember($context)
        ) {

            return $context;

        }




        $memory = new MemoryDTO(

            id: uniqid(),

            user_id: (string) $context->userId,

            text: $context->currentMessage,

            importance: 8,

            category: 'general',

            source_message: null,

            embedding: null,

            last_used: now()->toDateTimeString(),

            created_at: now()->toDateTimeString(),

        );



        $this->store->store(
            $memory
        );



        return $context;

    }







    /**
     * تصمیم اینکه چیزی ذخیره شود یا نه
     *
     * نسخه اول ساده است
     *
     * بعداً:
     *
     * LLM Classifier
     * Embedding
     * Importance Model
     *
     * اضافه می‌شود
     */
    protected function shouldRemember(
        ChatContext $context
    ): bool {


        $message =
            strtolower(
                $context->currentMessage
            );



        $keywords = [

            'من هستم',
            'من دارم',
            'یادم باشه',
            'همیشه',
            'ترجیح میدم'

        ];



        foreach ($keywords as $word) {

            if (
                str_contains(
                    $message,
                    $word
                )
            ) {

                return true;

            }

        }



        return false;

    }


}
