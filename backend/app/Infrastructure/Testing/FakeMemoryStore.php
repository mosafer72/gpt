<?php

namespace App\Infrastructure\Testing;

use App\Domain\Contracts\MemoryStore;
use App\Domain\Memory\DTOs\MemoryDTO;

class FakeMemoryStore implements MemoryStore
{
    public function store(MemoryDTO $memory): void
    {
        logger()->info('Memory Stored', [
            'memory' => $memory
        ]);
    }
}
