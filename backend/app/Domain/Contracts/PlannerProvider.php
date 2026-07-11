<?php

namespace App\Domain\Contracts;

use App\Application\DTOs\IntentResult;

interface PlannerProvider

{

    public function analyze(string $message): IntentResult;

}
