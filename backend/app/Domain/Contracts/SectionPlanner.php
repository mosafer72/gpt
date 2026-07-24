<?php

namespace App\Domain\Contracts;


interface SectionPlanner
{


    public function analyze(
        string $documentId,
        string $workspaceId,
        array $sections
    ): array;


}
