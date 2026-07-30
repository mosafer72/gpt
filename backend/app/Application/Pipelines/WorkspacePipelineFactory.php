<?php

namespace App\Application\Pipelines;

use App\Application\Pipelines\Academic\StudentWorkspacePipeline;
use App\Application\Pipelines\Chat\ChatWorkspacePipeline;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\Workspace\WorkspaceType;

class WorkspacePipelineFactory
{
    public function __construct(
        protected ChatWorkspacePipeline $chat,
        protected StudentWorkspacePipeline $academic
    ){}

    public function make(string $type):WorkspacePipeline
    {
        return $this->academic;
        return match($type){
            WorkspaceType::CHAT->value => $this->chat,
            WorkspaceType::ACADEMIC->value,
            'student' => $this->academic,
            default => throw new \Exception("Unsupported workspace type {$type}")
        };
    }
}
