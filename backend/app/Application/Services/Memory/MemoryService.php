<?php

namespace App\Application\Services\Memory;

use App\Infrastructure\Repositories\MemoryRepository;
use App\Domain\Memory\DTOs\MemoryDTO;

class MemoryService
{
    public function __construct(
        protected MemoryRepository $repository
    ) {}

    public function storeMemory(string $userId, string $text, int $importance = 5, string $category = 'general', ?string $sourceMessage = null): MemoryDTO
    {
        return $this->repository->create($userId, $text, $importance, $category, $sourceMessage);
    }

    public function getMemory(string $id): ?MemoryDTO
    {
        return $this->repository->find($id);
    }

    public function getUserMemories(string $userId, int $minImportance = 0, int $limit = 50): array
    {
        return $this->repository->findByUser($userId, $minImportance, $limit);
    }

    public function getMemoriesByCategory(string $userId, string $category, int $minImportance = 0): array
    {
        return $this->repository->findByCategory($userId, $category, $minImportance);
    }

    public function updateMemory(string $id, array $data): ?MemoryDTO
    {
        return $this->repository->update($id, $data);
    }

    public function touchMemory(string $id): ?MemoryDTO
    {
        return $this->repository->updateLastUsed($id);
    }

    public function deleteMemory(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function deleteAllUserMemories(string $userId): bool
    {
        return $this->repository->deleteByUser($userId);
    }
}
