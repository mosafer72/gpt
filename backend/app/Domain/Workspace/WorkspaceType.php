<?php

namespace App\Domain\Workspace;

enum WorkspaceType:string
{
    case CHAT='chat';
    case ACADEMIC='student';
}
