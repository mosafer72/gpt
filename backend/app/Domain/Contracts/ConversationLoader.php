<?php

namespace App\Domain\Contracts;

interface ConversationLoader

{

    public function load(string $conversationId, int $limit = 20): array;

}
