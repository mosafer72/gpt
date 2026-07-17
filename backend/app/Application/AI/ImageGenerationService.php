<?php

namespace App\Application\AI;

use App\Models\AiModel;
use OpenAI;

class ImageGenerationService
{
    public function generate(
        string $prompt,
        string $size,
        AiModel $model
    ): array {


        $provider = $model->provider;


        $client = OpenAI::factory()

            ->withApiKey(
                $provider->api_key
            )

            ->withBaseUri(
                $provider->base_url
            )

            ->make();



        $response = $client
            ->images()
            ->create([

                'model' =>
                    $model->model_name,

                'prompt' =>
                    $prompt,

                'size' =>
                    $size,

            ]);



        return [

            'url' =>
                $response->data[0]->url,

            'model' =>
                $model->model_name,

            'size' =>
                $size,

        ];
    }
}
