<?php

namespace App\Domain\LLM;

enum MessageRole: string
{
    case SYSTEM = 'system';

    case USER = 'user';

    case ASSISTANT = 'assistant';
}
