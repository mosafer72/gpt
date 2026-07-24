<?php

namespace App\Http\Controllers\Api;

use App\Application\Services\Documents\DocumentService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        protected DocumentService $service
    ) {
    }


    /**
     * List Documents
     */
    public function index(
        Request $request,
        $workspaceId
    ) {

        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);


        return response()->json([
            'success' => true,

            'documents' => $this->service->list(
                workspace: $workspace
            )
        ]);
    }



    /**
     * Upload Document
     */
    public function store(
        Request $request,
        $workspaceId
    ) {

        $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,txt',
                'max:20480'
            ]
        ]);


        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);



        $document = $this->service->upload(
            file: $request->file('file'),
            workspace: $workspace,
            user: $request->user()
        );


        return response()->json([
            'success' => true,
            'document' => $document
        ], 201);
    }





    /**
     * Show Document
     */
    public function show(
        Request $request,
        $workspaceId,
        $documentId
    ) {


        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);



        $document = $workspace
            ->documents()
            ->findOrFail($documentId);



        return response()->json([
            'success' => true,
            'document' => $this->service->show($document)
        ]);
    }





    /**
     * Rename Document
     */
    public function update(
        Request $request,
        $workspaceId,
        $documentId
    ) {


        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ]
        ]);



        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);



        $document = $workspace
            ->documents()
            ->findOrFail($documentId);



        $document = $this->service->rename(
            $document,
            $request->name
        );



        return response()->json([
            'success' => true,
            'document' => $document
        ]);
    }





    /**
     * Delete Document
     */
    public function destroy(
        Request $request,
        $workspaceId,
        $documentId
    ) {


        $workspace = $request->user()
            ->workspaces()
            ->findOrFail($workspaceId);



        $document = $workspace
            ->documents()
            ->findOrFail($documentId);



        $this->service->delete(
            $document
        );



        return response()->json([
            'success' => true
        ]);
    }
}
