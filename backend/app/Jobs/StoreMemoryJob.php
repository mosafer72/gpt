<?php

namespace App\Jobs;

use App\Application\Pipelines\MemoryPipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class StoreMemoryJob implements ShouldQueue
{
    use Queueable;


    public function __construct(
        public string $userId,
        public string $message
    ) {
    }



    public function handle(
        MemoryPipeline $pipeline
    ): void {


        \Log::info('STORE MEMORY JOB START', [
            'user_id' => $this->userId,
            'message' => $this->message,
        ]);


        $pipeline->storeMessage(
            userId: $this->userId,
            message: $this->message
        );



    }
}
