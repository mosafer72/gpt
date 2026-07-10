<?php

namespace App\Infrastructure\Memory;

use App\Domain\Contracts\MemoryStore;

use App\Domain\Memory\DTOs\MemoryDTO;

use App\Models\Memory;
use DB;

class DatabaseMemoryStore implements MemoryStore
{

    public function store(MemoryDTO $memory): void
    {

        Memory::create([

            'user_id' => $memory->user_id,

            'key' => 'memory_' . uniqid(),

            'value' => $memory->text,

            'source' => 'chat',

            'last_used_at' => now(),

        ]);

    }

}
