<?php

namespace App\Application\Services\Workspace;

use App\Application\Pipelines\ChatWorkspacePipeline;
use App\Application\Pipelines\StudentAssistantPipeline;

class WorkspacePipelineResolver
{

    public function resolve(string $type)
    {

        return match($type){

            'student_assistant'
                => app(StudentAssistantPipeline::class),

            default
                => app(ChatWorkspacePipeline::class)

        };

    }

}
