<?php

namespace App\Application\Services\Documents;


use App\Infrastructure\Repositories\DocumentSectionTranslationRepository;



class DocumentViewerService
{


    public function __construct(

        protected DocumentSectionTranslationRepository $repository

    )
    {

    }



    public function sections(

        string $documentId,

        string $workspaceId

    ): array {


        return $this->repository
            ->getByDocument(

                $documentId,

                $workspaceId

            );


    }


}
