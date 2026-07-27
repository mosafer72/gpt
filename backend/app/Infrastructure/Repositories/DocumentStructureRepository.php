<?php

namespace App\Infrastructure\Repositories;


use SurrealDB\Laravel\Facades\Surreal;
use SurrealDB\SDK\Query\BoundQuery;



class DocumentStructureRepository
{


    protected string $table = 'document_structure';






    public function store(
        array $data
    ): array {


        $response = Surreal::query(

            new BoundQuery(

                query: "

                    UPSERT {$this->table}

                    SET


                        document_id =
                        <string>\$document_id,


                        workspace_id =
                        <string>\$workspace_id,


                        title =
                        \$title,


                        sections =
                        \$sections,


                        statistics =
                        \$statistics,


                        metadata =
                        \$metadata,


                        created_at =
                        type::datetime(
                            \$created_at
                        ),


                        updated_at =
                        time::now();


                ",

                bindings: [


                    'document_id' =>
                        (string)
                        (
                            $data['document_id']
                            ??
                            ''
                        ),



                    'workspace_id' =>
                        (string)
                        (
                            $data['workspace_id']
                            ??
                            ''
                        ),



                    'title' =>
                        $data['title']
                        ??
                        [],



                    'sections' =>
                        $data['sections']
                        ??
                        [],



                    'statistics' =>
                        $data['statistics']
                        ??
                        [],



                    'metadata' =>
                        $data['metadata']
                        ??
                        [],



                    'created_at' =>
                        now()
                        ->toISOString()

                ]

            )

        );




        return $this->format(
            $response
        );


    }









    public function findByDocument(
        string $documentId
    ): array {


        $response = Surreal::query(

            new BoundQuery(

                query: "

                    SELECT *

                    FROM {$this->table}

                    WHERE document_id =
                    \$document_id

                    LIMIT 1;


                ",

                bindings: [

                    'document_id' =>
                        (string)
                        $documentId

                ]

            )

        );



        $result =
            $this->format(
                $response
            );



        return $result[0]
            ??
            [];

    }









    public function findByWorkspace(
        string $workspaceId
    ): array {


        $response = Surreal::query(

            new BoundQuery(

                query: "

                    SELECT *

                    FROM {$this->table}

                    WHERE workspace_id =
                    \$workspace_id

                    ORDER BY created_at DESC;


                ",

                bindings: [

                    'workspace_id' =>
                        (string)
                        $workspaceId

                ]

            )

        );



        return $this->format(
            $response
        );

    }









    public function sections(
        string $documentId
    ): array {


        $structure =
            $this->findByDocument(
                $documentId
            );



        return
            $structure['sections']
            ??
            [];

    }









    public function findSection(
        string $documentId,
        string $section
    ): array {


        $sections =
            $this->sections(
                $documentId
            );



        return collect($sections)

            ->filter(

                fn($item) =>

                    strtolower(
                        $item['name']
                        ??
                        ''
                    )

                    ===

                    strtolower(
                        $section
                    )

            )

            ->values()

            ->all();

    }









    public function deleteDocument(
        string $documentId
    ): mixed {


        return Surreal::query(

            new BoundQuery(

                query: "

                    DELETE FROM {$this->table}

                    WHERE document_id =
                    \$document_id;


                ",

                bindings: [

                    'document_id' =>
                        (string)
                        $documentId

                ]

            )

        );

    }









    public function debugDocument(
        string $documentId
    ): array {


        return $this->findByDocument(
            $documentId
        );

    }









    protected function format(
        mixed $response
    ): array {


        if (empty($response)) {

            return [];

        }



        if (

            is_object($response)

            &&

            method_exists(
                $response,
                'getData'
            )

        ) {


            $response =
                $response->getData();

        }



        if (!is_array($response)) {

            return [];

        }




        if (

            isset(
                $response[0]['result']
            )

            &&

            is_array(
                $response[0]['result']
            )

        ) {


            return collect(
                $response[0]['result']
            )
            ->values()
            ->all();

        }




        return collect($response)
            ->values()
            ->all();


    }


}
