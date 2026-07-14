<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ModelController extends Controller
{

    public function index()
    {

        $user = auth()->user();

        $models =
            $user->availableModels();

        return response()->json([

            'models' => $models

        ]);

    }

}
