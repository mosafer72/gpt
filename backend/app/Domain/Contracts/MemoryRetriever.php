<?php

namespace App\Domain\Contracts;

use App\Domain\Memory\DTOs\MemoryDTO;

interface MemoryRetriever
{
    /**
     * @return MemoryDTO[]
     */
    public function retrieve(
        string $userId,
        string $query,
        int $limit = 10
    ): array;
}
