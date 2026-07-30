<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceType;
use App\Models\WorkspacePlan;
use App\Application\Services\WorkspaceService;
use App\Application\Services\WorkspaceSubscriptionService;
use App\Application\Services\TokenWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class WorkspaceTypeController extends Controller
{


    public function __construct() {

    }



    public function index()
    {
        WorkspaceType::with("plans")->get();
    }

}
