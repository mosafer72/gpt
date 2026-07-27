<?php

namespace App\Providers;

use App\Application\AI\OpenAIEmbeddingProvider;
use App\Application\Services\Documents\DocumentOverviewService;
use App\Application\Services\Documents\DocumentRetrieverService;
use App\Domain\Contracts\ConversationRepository;
use App\Domain\Contracts\DocumentRetriever;
use App\Domain\Contracts\EmbeddingProvider;
use App\Domain\Contracts\MessageStore;
use App\Domain\Contracts\PlannerProvider;
use App\Domain\Contracts\SmallChatProvider;
use App\Domain\Documents\Contracts\DocumentChunkRepositoryInterface;
use App\Domain\Documents\Contracts\DocumentStructureRepositoryInterface;
use App\Domain\Documents\Contracts\DocumentVectorRepositoryInterface;
use App\Infrastructure\Conversation\DatabaseConversationLoader;
use App\Infrastructure\Embedding\BgeM3EmbeddingProvider;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Infrastructure\LLM\OllamaQwenChatProvider;
use App\Infrastructure\LLM\OpenAIProvider;
use App\Infrastructure\Memory\DatabaseMemoryRetriever;
use App\Infrastructure\Message\DatabaseMessageStore;
use App\Infrastructure\Planner\OllamaQwenPlannerProvider;
use App\Infrastructure\Repositories\DatabaseConversationRepository;
use App\Infrastructure\Repositories\DocumentChunkRepository;
use App\Infrastructure\Repositories\DocumentStructureRepository;
use Illuminate\Support\ServiceProvider;
use App\Infrastructure\Memory\DatabaseMemoryStore;

use App\Domain\Contracts\ConversationLoader;
use App\Domain\Contracts\MemoryRetriever;
use App\Domain\Contracts\MemoryStore;
use App\Domain\Contracts\LlmProvider;


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
            LlmProvider::class,
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
            OllamaQwenPlannerProvider::class
        );
        $this->app->bind(
            SmallChatProvider::class,
            OllamaQwenChatProvider::class
        );

        $this->app->bind(
            EmbeddingProvider::class,
            BgeM3EmbeddingProvider::class
        );
        $this->app->bind(

            DocumentRetriever::class,

            DocumentRetrieverService::class

        );

        $this->app->bind(
            DocumentOverviewService::class
        );


        $this->app->bind(

            DocumentChunkRepositoryInterface::class,

            DocumentChunkRepository::class

        );



        $this->app->bind(

            DocumentVectorRepositoryInterface::class,

            DocumentVectorRepositoryInterface::class

        );



        $this->app->bind(

            DocumentStructureRepositoryInterface::class,

            DocumentStructureRepository::class

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
