<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Surqlize Query Executor
    |--------------------------------------------------------------------------
    |
    | Surqlize executes compiled ORM queries through the SDK QueryExecutor
    | contract. The default points at the SurrealDB SDK container alias, but
    | tests or multi-connection applications may bind another executor.
    |
    */
    'executor' => env('SURQLIZE_EXECUTOR', 'surrealdb.connection'),

    /*
    |--------------------------------------------------------------------------
    | ORM Models
    |--------------------------------------------------------------------------
    */
     'models' => [
        \App\Domain\Conversation\Models\Conversation::class,
        // بعداً مدل‌های دیگر را اضافه کنید
        \App\Domain\Message\Models\Message::class,
        \App\Domain\Memory\Models\Memory::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Surqlize Schemas
    |--------------------------------------------------------------------------
    |
    | Here you can register all your Surqlize schema definitions.
    |
    */
    'schemas' => [
        \App\Domain\Conversation\Schemas\ConversationSchema::class,
        \App\Domain\Message\Schemas\MessageSchema::class,
        \App\Domain\Memory\Schemas\MemorySchema::class,
    ],
];
