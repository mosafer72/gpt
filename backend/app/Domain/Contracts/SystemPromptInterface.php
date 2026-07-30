<?php

namespace App\Domain\Contracts;

interface SystemPromptInterface
{
    public static function build(?array $variables = []): string;
}
