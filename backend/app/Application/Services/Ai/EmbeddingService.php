<?php

namespace App\Application\Services\Ai;

use Illuminate\Support\Facades\Http;


class EmbeddingService
{


    protected string $baseUrl;


    protected string $model;





    public function __construct()
    {


        $this->baseUrl =
            config(
                'services.local_ai.url',
                'http://localhost:11434/v1'
            );



        $this->model =
            config(
                'services.local_ai.embedding_model',
                'bge-m3'
            );


    }









    /**
     * Base embedding request
     */
    protected function request(
        array|string $input
    ): array {


        $response =
            Http::timeout(120)
            ->post(

                $this->baseUrl . '/embeddings',

                [

                    'model'=>
                        $this->model,


                    'input'=>
                        $input

                ]

            );





        if(!$response->successful()){


            throw new \Exception(

                'Embedding failed: '
                .
                $response->body()

            );

        }







        return collect(

            $response->json()['data'] ?? []

        )
        ->map(

            fn($item)=>

                $item['embedding'] ?? []

        )
        ->toArray();

    }









    /**
     * Single embedding
     */
    public function embed(
        string $text
    ): array {


        $result =
            $this->request(
                $text
            );



        return $result[0] ?? [];

    }









    /**
     * Document embedding
     *
     * bge-m3 instruction:
     * passage:
     */
    public function embedDocument(
        string $text
    ): array {


        return $this->embed(

            "passage: "
            .
            $text

        );

    }









    /**
     * Query embedding
     *
     * bge-m3 instruction:
     * query:
     */
    public function embedQuery(
        string $text
    ): array {



        $query =

        "
query: {$text}

Find relevant academic information about:
methodology,
architecture,
approach,
implementation,
experiments,
evaluation,
results.
";



        return $this->embed(
            $query
        );

    }









    /**
     * Batch embedding
     */
    public function embedMany(
        array $texts
    ): array {


        if(empty($texts)){

            return [];

        }





        $inputs =
            array_map(

                fn($text)=>

                    "passage: ".$text,

                $texts

            );





        return $this->request(
            $inputs
        );

    }


}
