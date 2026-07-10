<?php

namespace App\Domain\Contracts;

use App\Domain\Conversation\DTOs\ConversationDTO;

interface ConversationRepository
{

    public function find(
        string $id
    ): ?ConversationDTO;


    public function create(
        string $userId,
        ?string $title = null,
        ?string $model = null
    ): ConversationDTO;


    public function update(
        string $id,
        array $data
    ): void;


    public function delete(
        string $id
    ): void;

}
