<?php

namespace App\Infrastructure\LLM;


use Illuminate\Support\Facades\Http;


class CloudflareTextProvider
{


    public function stream(
        array $messages,
        string $model = null
    )
    {


        $response = Http::withToken(
            config('services.cloudflare.ai_key')
        )
        ->withHeaders([
            "Content-Type"=>"application/json"
        ])
        ->withOptions([
            'stream'=>true
        ])
        ->post(
            config('services.cloudflare.ai_url'),
            [

                "model" =>
                    $model ??
                    "@cf/qwen/qwen2.5-14b-instruct",


                "messages"=>$messages,


                "stream"=>true

            ]
        );


        return $response->stream();

    }

}
