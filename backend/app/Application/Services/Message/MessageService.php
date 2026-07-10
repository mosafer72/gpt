<?php

namespace App\Application\Services\Message;

use App\Infrastructure\Repositories\MessageRepository;
use App\Domain\Message\DTOs\MessageDTO;

class MessageService
{
    public function __construct(
        protected MessageRepository $repository
    ) {}

    public function addMessage(string $conversationId, string $role, string $content, int $tokenCount = 0, ?array $metadata = null): MessageDTO
    {
        return $this->repository->create($conversationId, $role, $content, $tokenCount, $metadata);
    }

    public function getMessage(string $id): ?MessageDTO
    {
        return $this->repository->find($id);
    }

    public function getConversationMessages(string $conversationId, int $limit = 50): array
    {
        return $this->repository->findByConversation($conversationId, $limit);
    }

    public function getLastMessages(string $conversationId, int $limit = 10): array
    {
        return $this->repository->findLast($conversationId, $limit);
    }

    public function updateMessage(string $id, array $data): ?MessageDTO
    {
        return $this->repository->update($id, $data);
    }

    public function deleteMessage(string $id): bool
    {
        return $this->repository->delete($id);
    }

    public function deleteConversationMessages(string $conversationId): bool
    {
        return $this->repository->deleteByConversation($conversationId);
    }
}
