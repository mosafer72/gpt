<?php
namespace App\Application\Services\Documents;

class Chunker
{
    protected int $maxWords=700;
    protected int $minWords=30;

    public function split(array $sections):array
    {
        if(empty($sections))return [];

        $chunks=[];
        $index=0;

        foreach($sections as $section){

            $content=trim($section['content']??'');

            if(blank($content))continue;

            $title=trim($section['title']??'Document');

            $normalized=$this->normalize(
                $section['normalized']??$title
            );

            $parts=$this->splitContent($content);

            foreach($parts as $part){

                $part=trim($part);

                if($this->wordCount($part)<$this->minWords)continue;

                $chunks[]=[
                    'chunk_index'=>$index++,
                    'section'=>$normalized?:'general',
                    'section_title'=>$title,
                    'heading_path'=>[$title],
                    'word_count'=>$this->wordCount($part),
                    'importance'=>$this->importance($normalized),
                    'content'=>"# ".$title."\n\n".$part
                ];
            }
        }

        return $chunks;
    }


    protected function splitContent(string $text):array
    {
        $words=preg_split('/\s+/u',trim($text));

        if(count($words)<=$this->maxWords)return [$text];

        $chunks=[];
        $buffer=[];

        foreach($words as $word){

            $buffer[]=$word;

            if(count($buffer)>=$this->maxWords){

                $chunks[]=implode(' ',$buffer);

                $buffer=[];
            }
        }

        if(!empty($buffer)){
            $chunks[]=implode(' ',$buffer);
        }

        return $chunks;
    }


    protected function wordCount(string $text):int
    {
        if(blank($text))return 0;

        return count(
            preg_split('/\s+/u',trim($text))
        );
    }


    protected function importance(string $section):string
    {
        return match(true){

            str_contains($section,'abstract'),
            str_contains($section,'introduction'),
            str_contains($section,'conclusion')
            =>'high',

            str_contains($section,'method'),
            str_contains($section,'methodology'),
            str_contains($section,'approach'),
            str_contains($section,'framework'),
            str_contains($section,'architecture'),
            str_contains($section,'model'),
            str_contains($section,'algorithm'),
            str_contains($section,'experiment'),
            str_contains($section,'evaluation'),
            str_contains($section,'results')
            =>'very_high',

            str_contains($section,'references'),
            str_contains($section,'appendix')
            =>'low',

            default=>'medium'
        };
    }


    protected function normalize(string $value):string
    {
        return mb_strtolower(
            trim(
                preg_replace(
                    '/[^\p{L}\p{N}\s]/u',
                    '',
                    $value
                )
            )
        );
    }
}
