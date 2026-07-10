<?php

namespace App\Domain\Contracts;


use App\Domain\Memory\DTOs\MemoryDTO;


interface MemoryStore
{


    public function store(
        MemoryDTO $memory
    ): void;


}
