<?php

namespace App\Application\Services\Documents;


use App\Domain\Contracts\SectionSelector;
use App\Infrastructure\Repositories\DocumentSectionPlanRepository;



class SectionSelectorService implements SectionSelector
{


    public function __construct(

        protected DocumentSectionPlanRepository $repository

    ) {

    }





    public function select(

        string $workspaceId,

        string $documentId,

        string $question,

        int $limit = 3

    ): array {


        $sections =

            $this->repository
                ->searchRelevantSections(

                    workspaceId:
                    $workspaceId,


                    documentId:
                    $documentId,


                    question:
                    $question,


                    limit:
                    $limit

                );




        return collect($sections)

            ->sortByDesc(
                fn($item) =>
                $item['answer_probability']
                ??
                0
            )

            ->take($limit)

            ->values()

            ->all();


    }



}
