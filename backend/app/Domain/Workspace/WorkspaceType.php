<?php

namespace App\Domain\Workspace;

enum WorkspaceType:string
{
    case CHAT='personal';
    case ACADEMIC='student';
}
