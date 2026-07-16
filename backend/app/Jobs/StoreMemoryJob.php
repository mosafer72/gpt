<?php

namespace App\Jobs;

use App\Application\Pipelines\MemoryPipeline;
use App\Application\Context\ChatContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreMemoryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $contextData) {}

    public function handle(MemoryPipeline $pipeline): void
    {
        $context = ChatContext::fromArray($this->contextData);

        $pipeline->store($context);
    }
}
