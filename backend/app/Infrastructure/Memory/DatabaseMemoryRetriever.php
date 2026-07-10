<?php

namespace App\Infrastructure\Memory;

use App\Domain\Contracts\MemoryRetriever;
use App\Models\Memory;


class DatabaseMemoryRetriever implements MemoryRetriever
{


    public function retrieve(string $userId, string $query, int $limit = 10): array
    {

        return Memory::where('user_id', $userId)

            ->latest('last_used_at')

            ->limit($limit)

            ->get()

            ->map(function ($memory) {

                return (object) [

                    'content' => $memory->value,

                ];

            })

            ->toArray();

    }

}
