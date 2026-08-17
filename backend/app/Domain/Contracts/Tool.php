<?php

namespace App\Domain\Contracts;

interface Tool
{
    public function name(): string;

    public function execute(array $input): array;

    public function enabled(): bool;
}
