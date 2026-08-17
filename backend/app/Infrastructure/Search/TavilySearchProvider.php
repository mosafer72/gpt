<?php

namespace App\Infrastructure\Search;

use App\Domain\Contracts\SearchProvider;
use Illuminate\Support\Facades\Http;

class TavilySearchProvider implements SearchProvider
{

    public function search(string $query): array
    {

        $response = Http::withToken(
            config('services.tavily.api_key')
        )
        ->post(
            'https://api.tavily.com/search',
            [
                'query'=>$query,
                'search_depth'=>'basic',
                'topic'=>'general',
                'max_results'=>3,
                'include_answer'=>false,
                'include_raw_content'=>false,
            ]
        );


        if (!$response->successful()) {
            return [];
        }


        $results = $response->json('results', []);


        return collect($results)
            ->map(function($item){

                return [

                    'title'=>$this->normalize(
                        $item['title'] ?? ''
                    ),

                    'url'=>$item['url'] ?? '',


                    'snippet'=>$this->normalize(
                        mb_substr(
                            $item['content'] ?? '',
                            0,
                            700
                        )
                    ),

                ];

            })
            ->values()
            ->toArray();

    }



    protected function normalize(string $text): string
    {

        $text = str_replace(
            [
                '۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'
            ],
            [
                '0','1','2','3','4','5','6','7','8','9'
            ],
            $text
        );


        $text = str_replace(
            [
                'ي',
                'ك'
            ],
            [
                'ی',
                'ک'
            ],
            $text
        );


        $text = preg_replace(
            '/[\x{200B}-\x{200F}\x{202A}-\x{202E}]/u',
            '',
            $text
        );


        $text = preg_replace(
            '/\s+/u',
            ' ',
            $text
        );


        return trim($text);
    }

}
