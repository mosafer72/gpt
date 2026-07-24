<?php

namespace App\Application\Services\Documents;


use App\Application\Services\Ai\EmbeddingService;
use App\Infrastructure\Repositories\DocumentSectionRepository;



class DocumentSectionService
{


    public function __construct(

        protected EmbeddingService $embeddingService,

        protected DocumentSectionRepository $repository

    )
    {

    }






    public function createSection(

        string $documentId,

        string $workspaceId,

        string $section,

        array $chunks

    ) {



        $content = collect($chunks)

            ->pluck('content')

            ->implode("\n");





        $summaryText =

            "Academic paper section:
            {$section}

            Content:

            {$content}";





        $embedding =

            $this->embeddingService
                ->embedDocument(
                    $summaryText
                );






        $this->repository->store([


            'document_id'
                =>
                $documentId,


            'workspace_id'
                =>
                $workspaceId,


            'section'
                =>
                $section,


            'title'
                =>
                ucfirst($section),


            'summary'
                =>
                mb_substr(
                    $content,
                    0,
                    1000
                ),


            'embedding'
                =>
                $embedding,


            'chunk_ids'
                =>
                collect($chunks)
                    ->pluck('chunk_id')
                    ->values()
                    ->all()

        ]);

    }


}
