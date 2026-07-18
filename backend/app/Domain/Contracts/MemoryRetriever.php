<?php

namespace App\Domain\Contracts;

use App\Domain\Memory\DTOs\MemoryDTO;

interface MemoryRetriever
{
    /**
     * Retrieve relevant memories for a user.
     *
     * @param string $userId
     * @param string $query Current user message/query
     * @param int $limit Maximum memories to return
     *
     * @return MemoryDTO[]
     */
    public function retrieve(
        string $userId,
        string $query,
        int $limit = 10
    ): array;
}
