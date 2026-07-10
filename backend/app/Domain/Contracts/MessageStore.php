<?php

namespace App\Domain\Contracts;

use App\Domain\Message\DTOs\MessageDTO;

interface MessageStore
{
    public function store(
        MessageDTO $message
    ): void;
}
