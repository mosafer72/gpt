<?php

namespace App\Application\AI;


use App\Infrastructure\LLM\Cloudflare\CloudflareImageProvider;
use App\Models\AiModel;



class ImageGenerationService
{

    public function __construct(
        protected CloudflareImageProvider $CloudflareImageProvider
    ) {

    }



    public function generate(
        string $prompt,
        string $size,
        AiModel $model
    ): array {

        $imageProvider = \App\Models\LlmProvider::where("id", $model->provider_id)->first();

        $result =
             $this->{$imageProvider->name}->generate(

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
