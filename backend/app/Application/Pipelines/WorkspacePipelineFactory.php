<?php

namespace App\Application\Pipelines;

use App\Application\Pipelines\Academic\StudentWorkspacePipeline;
use App\Application\Pipelines\Chat\ChatWorkspacePipeline;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\Workspace\WorkspaceType;

class WorkspacePipelineFactory
{
    public function __construct(
        protected ChatWorkspacePipeline $personal,
        protected StudentWorkspacePipeline $academic
    ){}

    public function make(string $type):WorkspacePipeline
    {

        return match($type){
            WorkspaceType::CHAT->value => $this->personal,
            WorkspaceType::ACADEMIC->value,
            'student' => $this->academic,
            default => throw new \Exception("Unsupported workspace type {$type}")
        };
    }
}
