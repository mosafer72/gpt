<?php

namespace App\Infrastructure\Memory;

use App\Domain\Contracts\MemoryStore;
use App\Domain\Memory\DTOs\MemoryDTO;


class FakeMemoryStore implements MemoryStore
{

    public function store(
        MemoryDTO $memory
    ): void {

        // فعلاً فقط تست
        logger()->info(
            'Memory stored',
            $memory->toArray()
        );

    }

}
