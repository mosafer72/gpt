<?php

namespace App\Infrastructure\Search;

use Illuminate\Support\Facades\Http;

class CloudflareSearchProvider
{

    public function search(
        string $query
    ): array {


        $response = Http::withToken(
            config('services.cloudflare.token')
        )
        ->post(
            'https://api.cloudflare.com/client/v4/accounts/'
            .config('services.cloudflare.account_id')
            .'/ai/run/@cf/meta/llama-3.1-8b-instruct',
            [
                'messages'=>[
                    [
                        'role'=>'system',
                        'content'=>
                        'You are a web search assistant. Return search results.'
                    ],
                    [
                        'role'=>'user',
                        'content'=>$query
                    ]
                ]
            ]
        );


        if (!$response->successful()) {
            return [];
        }


        $data = $response->json();


        return [
            [
                'title'=>'Cloudflare Search',
                'url'=>'',
                'snippet'=>$data['result']['response'] ?? ''
            ]
        ];

    }

}
