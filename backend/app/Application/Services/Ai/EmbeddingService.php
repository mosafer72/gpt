<?php
namespace App\Application\Services\Ai;
use Illuminate\Support\Facades\Http;

class EmbeddingService
{
    protected string $baseUrl;
    protected string $model;

    public function __construct()
    {
        $this->baseUrl=rtrim(config('services.local_ai.url','http://localhost:11434/v1'),'/');
        $this->model=config('services.local_ai.embedding_model','bge-m3');
    }

    protected function request(array|string $input):array
    {
        $response=Http::timeout(120)->post($this->baseUrl.'/embeddings',[
            'model'=>$this->model,
            'input'=>$input
        ]);

        if(!$response->successful())throw new \Exception('Embedding failed: '.$response->body());

        $data=$response->json('data',[]);

        return collect($data)->map(fn($item)=>$item['embedding']??[])->filter()->values()->all();
    }

    public function embed(string $text):array
    {
        return $this->request($text)[0]??[];
    }

    public function embedDocument(string $text):array
    {
        return $this->embed("passage: ".$this->clean($text));
    }

    public function embedQuery(string $text):array
    {
        return $this->embed("query: ".$this->clean($text)."\nFind relevant academic information about methodology, architecture, approach, implementation, experiments, evaluation and results.");
    }

    public function embedMany(array $texts):array
    {
        if(empty($texts))return [];

        return $this->request(
            array_map(fn($text)=>"passage: ".$this->clean($text),$texts)
        );
    }

    protected function clean(string $text):string
    {
        return trim(
            preg_replace('/\s+/u',' ',$text)
        );
    }
}
