<?php

namespace App\Providers;

use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\PlannerProvider;
use App\Infrastructure\Conversation\DatabaseConversationLoader;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Infrastructure\LLM\OpenAIProvider;
use App\Infrastructure\Memory\DatabaseMemoryRetriever;
use App\Infrastructure\Message\DatabaseMessageStore;
use App\Infrastructure\Planner\GapGPTPlannerProvider;
use App\Infrastructure\Repositories\DatabaseConversationRepository;
use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Memory\DatabaseMemoryStore;

use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\MemoryRetriever;
use App\Domain\Contracts\MemoryStore;
use App\Domain\Contracts\LLMProvider;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {


        $this->app->bind(
            MessageStore::class,
            DatabaseMessageStore::class
        );
        $this->app->bind(
            ConversationRepository::class,
            DatabaseConversationRepository::class
        );
        $this->app->bind(
            ConversationLoader::class,
            DatabaseConversationLoader::class
        );

        $this->app->bind(
            LLMProvider::class,
            GapGPTProvider::class
        );

        $this->app->bind(
            MemoryRetriever::class,
            DatabaseMemoryRetriever::class
        );
        $this->app->bind(
            MemoryStore::class,
            DatabaseMemoryStore::class
        );
        $this->app->bind(

            PlannerProvider::class,

            GapGPTPlannerProvider::class


        );

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
