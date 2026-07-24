<?php

namespace App\Infrastructure\Embedding;

use App\Domain\Contracts\EmbeddingProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;



class BgeM3EmbeddingProvider implements EmbeddingProvider
{

    protected string $url;

    protected string $model;

    protected int $batchSize;



    public function __construct(
        int $batchSize = 16
    ) {

        $this->url = rtrim(
            config('services.ollama.url'),
            '/'
        ) . '/api/embed';


        $this->model = config(
            'services.embedding.model',
            'bge-m3'
        );


        $this->batchSize = $batchSize;
    }



    public function embed(
        string $text
    ): array {

        $text = trim($text);


        if ($text === '') {
            return [];
        }


        $embeddings = $this->request([
            $text
        ]);


        return $embeddings[0] ?? [];
    }





    public function embedMany(
        array $texts
    ): array {


        $texts = collect($texts)
            ->map(
                fn($text) => trim((string) $text)
            )
            ->filter()
            ->values()
            ->all();



        if (empty($texts)) {
            return [];
        }



        $batches = array_chunk(
            $texts,
            $this->batchSize
        );



        $result = [];



        foreach ($batches as $batch) {


            $embeddings = $this->request(
                $batch
            );


            $result = array_merge(
                $result,
                $embeddings
            );
        }



        return $result;
    }





    protected function request(
        array $inputs
    ): array {


        $start = microtime(true);



        $response = Http::retry(
                3,
                500
            )
            ->timeout(120)
            ->post(
                $this->url,
                [
                    'model' => $this->model,

                    'input' => $inputs,
                ]
            );



        if (! $response->successful()) {


            Log::error(
                'BGE-M3 embedding request failed',
                [
                    'status' => $response->status(),

                    'body' => $response->body(),
                ]
            );


            throw new \RuntimeException(
                'Embedding failed: ' .
                $response->body()
            );
        }



        $embeddings = $response->json(
            'embeddings'
        );



        if (
            !is_array($embeddings)
            ||
            count($embeddings) !== count($inputs)
        ) {


            throw new \RuntimeException(
                'Invalid embedding response structure.'
            );
        }



        Log::info(
            'BGE-M3 embeddings processed',
            [
                'chunks' => count($inputs),

                'time_ms' => round(
                    (microtime(true) - $start) * 1000
                ),

                'dimension' => count(
                    $embeddings[0] ?? []
                ),
            ]
        );



        return $embeddings;
    }

}
