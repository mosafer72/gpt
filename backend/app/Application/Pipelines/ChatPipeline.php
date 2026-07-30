<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Application\Pipelines\Chat\ChatWorkspacePipeline;
use App\Application\Pipelines\Academic\StudentWorkspacePipeline;
use App\Application\Services\WorkspaceService;
use App\Domain\Contracts\WorkspacePipeline;
use App\Models\User;

class ChatPipeline implements WorkspacePipeline
{
    public function __construct(
        protected ChatWorkspacePipeline $chatWorkspacePipeline,
        protected StudentWorkspacePipeline $studentWorkspacePipeline,
        protected WorkspaceService $workspaceService,
    ) {
    }

    public function handle(ChatContext $context): ChatContext
    {
        $pipeline=$this->resolvePipeline($context);

        return $pipeline->handle($context);
    }

    public function stream(ChatContext $context,callable $send):void
    {
        $pipeline=$this->resolvePipeline($context);

        $pipeline->stream($context,$send);
    }

    protected function resolvePipeline(ChatContext $context):WorkspacePipeline
    {
        $user=User::findOrFail($context->userId);

        $workspace=$this->workspaceService->current($user);

        if(!$workspace){
            throw new \RuntimeException('Workspace not found');
        }

        $context->setWorkspace($workspace);

        // if($this->isAcademicWorkspace($workspace)){
        //     return $this->studentWorkspacePipeline;
        // }
        return $this->chatWorkspacePipeline;
    }

    protected function isAcademicWorkspace($workspace):bool
    {
        // return $workspace->type==='student'
        //     || $workspace->type==='academic'
        //     || $workspace->has_documents;
        return false;
    }
}
