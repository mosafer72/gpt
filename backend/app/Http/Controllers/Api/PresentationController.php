<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Domain\Presentation\Services\PresentationService;
use App\Domain\Presentation\DTO\CreatePresentationDTO;


class PresentationController extends Controller
{

    public function __construct(
        protected PresentationService $service
    ) {
    }





    public function index(
        Request $request,
        $workspace
    ) {

        return response()->json(

            $this->service->list(
                $workspace
            )

        );

    }





    public function store(
        Request $request,
        $workspace
    ) {


        $validated = $request->validate([

            'message' => [
                'required',
                'string',
                'min:5'
            ]

        ]);



        $dto = CreatePresentationDTO::from(

            $validated

        );



        $presentation = $this->service->create(

            $workspace,

            auth()->id(),

            $dto

        );



        return response()->json([

            'success' => true,

            'message' =>
                'درخواست ساخت پاورپوینت ارسال شد',

            'presentation' =>
                $presentation

        ], 201);


    }





    public function download(
        $workspace,
        $document
    ) {


        return response()->download(

            storage_path(
                'app/' . $document->path
            )

        );

    }


}
