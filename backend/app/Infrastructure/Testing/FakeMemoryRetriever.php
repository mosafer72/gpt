<?php

namespace App\Infrastructure\Testing;


use App\Domain\Contracts\MemoryRetriever;



class FakeMemoryRetriever implements MemoryRetriever
{


    public function retrieve(
        string $userId,
        string $query,
        int $limit = 10
    ): array {


        return [

            (object)[
                "content"=>"User works with Laravel"
            ]

        ];

    }

}
