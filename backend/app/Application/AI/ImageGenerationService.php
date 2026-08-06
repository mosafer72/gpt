<?php

namespace App\Application\AI;


use App\Infrastructure\LLM\CloudflareImageProvider;
use App\Models\AiModel;



class ImageGenerationService
{

    public function __construct(
        protected CloudflareImageProvider $provider
    ) {

    }



    public function generate(
        string $prompt,
        string $size,
        AiModel $model
    ): array {

        $result =
            $this->provider->generate(

                prompt: $prompt,

                model: $model

            );



        return [

            'url' =>
                $result['url'],


            'path' =>
                $result['path'],


            'model' =>
                $model->model_name,


            'size' =>
                $size,

        ];

    }

}
