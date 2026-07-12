<?php

namespace App\Domain\Contracts;

use App\Application\DTOs\IntentResult;
use App\Application\Context\ChatContext;


interface PlannerProvider

{

public function analyze(ChatContext $context): IntentResult;

}
