<?php

namespace App\Domain\Contracts;

interface SearchProvider
{
    public function search(string $query): array;
}
