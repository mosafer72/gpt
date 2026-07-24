<?php

namespace App\Application\DTOs;

class IntentResult
{

    public function __construct(

        public string $intent,

        public float $confidence,

        public string $rewrite,

        public bool $needsClarification = false,

        public bool $useMainModel = true,

    ) {}

}
