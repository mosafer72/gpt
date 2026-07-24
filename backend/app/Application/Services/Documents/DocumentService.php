<?php

namespace App\Application\Services\Documents;


use App\Models\Document;
use App\Jobs\ProcessDocumentJob;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;



class DocumentService
{


    /**
     * Upload Document
     */
    public function upload(
        UploadedFile $file,
        $workspace,
        $user
    ): Document {


        /**
         * Store file
         */
        $path =
            $file->store(
                'workspace-documents',
                'public'
            );



        /**
         * Create Document
         */
        $document =
            Document::create([


                'workspace_id'
                =>
                    $workspace->id,


                'user_id'
                =>
                    $user->id,


                'name'
                =>
                    $file->getClientOriginalName(),


                'path'
                =>
                    $path,


                'mime_type'
                =>
                    $file->getMimeType(),


                'size'
                =>
                    $file->getSize(),


                'status'
                =>
                    'uploaded'


            ]);





        /**
         * Start processing
         *
         * Extract text
         * Chunking
         * Embedding (later)
         */
        ProcessDocumentJob::dispatch(
            $document
        );



        return $document;


    }




    /**
     * List workspace documents
     */
    /**
     * List workspace documents
     */
    public function list(
        $workspace,
        ?string $search = null,
        ?string $status = null
    ) {

        $query = $workspace
            ->documents()
            ->latest();

        if (!empty($search)) {

            $query->where(
                'name',
                'like',
                "%{$search}%"
            );

        }

        if (
            !empty($status)
            &&
            $status !== 'all'
        ) {

            $query->where(
                'status',
                $status
            );

        }

        return $query->get();

    }





    /**
     * Delete document
     */
    public function delete(
        Document $document
    ) {


        /**
         * Delete file
         */
        if (
            $document->path
        ) {

            Storage::disk('public')
                ->delete(
                    $document->path
                );

        }



        /**
         * Delete chunks
         * (cascade DB handles it)
         */
        $document->delete();



    }

    /**
     * Show Document Detail
     */
    public function show(
        Document $document
    ): Document {
        return $document->load([
            // relations
        ]);
    }
}
