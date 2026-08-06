<?php

namespace App\Infrastructure\LLM;


use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;



class CloudflareImageProvider
{


    public function generate(
        string $prompt,
        ?string $model = null
    ): array {

        $payload = [
            'prompt' => $prompt,
        ];


        // if ($model) {
        //     $payload['model'] = $model;
        // }


        $response = Http::timeout(180)
            ->withToken(
                config('services.cloudflare.image_worker_key')
            )
            ->post(
                config('services.cloudflare.image_worker_url'),
                $payload
            );

        if (!$response->successful()) {

            throw new \RuntimeException(
                'Image generation failed: ' .
                $response->body()
            );
        }


        $filename =
            'chat-images/' .
            uniqid('img_') .
            '.png';


        Storage::disk('public')
            ->put(
                $filename,
                $response->body()
            );


        return [
            'path' => $filename,
            'url' => rtrim(env('BACK_URL'), '/') . parse_url(Storage::disk('public')->url($filename), PHP_URL_PATH)
        ];
    }
}
