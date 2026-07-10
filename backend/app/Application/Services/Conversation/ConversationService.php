<?php

namespace App\Application\Services\Conversation;

use App\Domain\Contracts\ConversationRepository;
use App\Domain\Conversation\DTOs\ConversationDTO;

class ConversationService
{
    public function __construct(
        protected ConversationRepository $repository
    ) {
    }

    // ──────────────── CREATE ────────────────

    public function startNewConversation(string $userId, ?string $title = null): ConversationDTO
    {
        $data = [
            'title' => $title ?? 'New Conversation',
            'model' => config('openai.default_model', 'gpt-4o'),
        ];

        return $this->repository->create($userId, $data);
    }

    // ──────────────── READ ────────────────

    public function getConversation(string $id): ?ConversationDTO
    {
        return $this->repository->find($id);
    }

    public function getUserConversations(string $userId): array
    {
        return $this->repository->findByUser($userId);
    }

    // ──────────────── UPDATE ────────────────

    public function updateConversation(string $id, array $data): ?ConversationDTO
    {
        return $this->repository->update($id, $data);
    }

    // ──────────────── SOFT DELETE ────────────────

    public function deleteConversation(string $id, bool $softDelete = true)
    {
        if ($softDelete) {
            return $this->repository->softDelete($id);
        }

        return $this->repository->hardDelete($id);
    }

    public function deleteAllUserConversations(
        string $userId,
        bool $softDelete = true
    ) {
        if ($softDelete) {
            return $this->repository->softDeleteByUser($userId);
        }

        return $this->repository->hardDeleteByUser($userId);
    }

    // ──────────────── RESTORE ────────────────

    public function restoreConversation(string $id): ?ConversationDTO
    {
        return $this->repository->restore($id);
    }

    // ──────────────── ARCHIVE ────────────────

    public function archiveConversation(string $id): ?ConversationDTO
    {
        return $this->repository->archive($id);
    }

    public function archiveAllUserConversations(string $userId): array
    {
        return $this->repository->archiveByUser($userId);
    }

    // ──────────────── HARD DELETE ────────────────

    public function destroyConversation(string $id): bool
    {
        return $this->repository->hardDelete($id);
    }

    public function destroyAllUserConversations(string $userId): bool
    {
        return $this->repository->hardDeleteByUser($userId);
    }
}
