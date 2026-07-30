<?php

namespace App\Application\Services\Documents;

class DocumentSectionDetector
{
    protected array $patterns=[
        'abstract'=>['abstract','summary','overview'],
        'introduction'=>['introduction','motivation','problem statement','research background'],
        'related_work'=>['related work','related studies','literature review','previous work','state of the art','background'],
        'methodology'=>['method','methods','methodology','approach','proposed method','proposed approach','framework','architecture','model','algorithm','design'],
        'dataset'=>['dataset','datasets','data collection','corpus','benchmark','data description'],
        'implementation'=>['implementation','system implementation','development','engineering'],
        'experiment'=>['experiment','experiments','experimental setup','evaluation setup','experimental design'],
        'results'=>['results','findings','performance','analysis','comparison','evaluation'],
        'discussion'=>['discussion','limitations','implications'],
        'conclusion'=>['conclusion','concluding remarks','future work'],
        'references'=>['references','bibliography','citations'],
        'appendix'=>['appendix']
    ];

    public function detect(string $text):array
    {
        $text=trim(str_replace("\r","",$text));

        if(blank($text))return [];

        $lines=explode("\n",$text);

        if(count($lines)<10){

            return [[
                'title'=>'Article',
                'normalized'=>'article',
                'level'=>1,
                'content'=>$text,
                'start_line'=>0,
                'end_line'=>count($lines)
            ]];
        }

        $heads=[];

        foreach($lines as $i=>$line){

            $line=trim($line);

            if(blank($line))continue;

            $heading=$this->detectHeading($line);

            if($heading){

                if($heading['type']==='references'){
                    continue;
                }

                $heads[]=[
                    'line'=>$i,
                    'title'=>$heading['title'],
                    'type'=>$heading['type'],
                    'level'=>$heading['level']
                ];
            }
        }


        if(empty($heads)){

            return [[
                'title'=>'Article',
                'normalized'=>'article',
                'level'=>1,
                'content'=>$text,
                'start_line'=>0,
                'end_line'=>count($lines)
            ]];
        }


        $sections=[];


        foreach($heads as $i=>$head){

            $start=$head['line'];

            $end=$heads[$i+1]['line']??count($lines);


            $content=trim(
                implode(
                    "\n",
                    array_slice(
                        $lines,
                        $start,
                        $end-$start
                    )
                )
            );


            if(
                $head['type']==='references'
                ||
                $this->wordCount($content)<30
            ){
                continue;
            }


            $sections[]=[
                'title'=>$head['title'],
                'normalized'=>$head['type'],
                'level'=>$head['level'],
                'content'=>$content,
                'start_line'=>$start,
                'end_line'=>$end
            ];
        }


        if(empty($sections)){

            return [[
                'title'=>'Article',
                'normalized'=>'article',
                'level'=>1,
                'content'=>$text,
                'start_line'=>0,
                'end_line'=>count($lines)
            ]];
        }


        return $this->mergeShortSections($sections);
    }


    protected function detectHeading(string $line):?array
    {
        if(
            preg_match('/https?:\/\//i',$line)
            ||
            str_contains($line,'@')
        ){
            return null;
        }


        if(mb_strlen($line)>80)return null;


        if($this->looksLikeSentence($line))return null;


        $clean=trim(
            preg_replace(
                '/^[\d\.\-\s]+/',
                '',
                $line
            )
        );


        if(blank($clean))return null;


        $type=$this->classify($clean);


        if($type!=='unknown'){

            return [
                'title'=>$clean,
                'type'=>$type,
                'level'=>$this->level($line)
            ];
        }


        if(
            preg_match(
                '/^(\d+(\.\d+)*)\s+(.+)$/',
                $line,
                $m
            )
        ){

            $candidate=trim($m[3]);


            if(!$this->looksLikeSentence($candidate)){

                $type=$this->classify($candidate);

                if($type!=='unknown'){

                    return [
                        'title'=>$candidate,
                        'type'=>$type,
                        'level'=>substr_count($m[1],'.')+1
                    ];
                }
            }
        }


        if(
            mb_strlen($clean)<50
            &&
            strtoupper($clean)===$clean
        ){

            $type=$this->classify($clean);

            if($type!=='unknown'){

                return [
                    'title'=>$clean,
                    'type'=>$type,
                    'level'=>1
                ];
            }
        }


        return null;
    }


    protected function classify(string $title):string
    {
        $title=$this->normalize($title);


        foreach($this->patterns as $type=>$words){

            foreach($words as $word){

                $word=$this->normalize($word);


                if($title===$word){
                    return $type;
                }


                if(
                    mb_strlen($title)<40
                    &&
                    str_contains($title,$word)
                ){
                    return $type;
                }
            }
        }


        return 'unknown';
    }


    protected function looksLikeSentence(string $line):bool
    {
        $words=preg_split('/\s+/u',trim($line));


        if(count($words)>8){
            return true;
        }


        if(str_ends_with($line,'.')){
            return true;
        }


        if(
            preg_match(
                '/\b(is|are|was|were|which|that|can|show|we|this|these|using)\b/i',
                $line
            )
        ){
            return true;
        }


        return false;
    }


    protected function mergeShortSections(array $sections):array
    {
        $result=[];


        foreach($sections as $section){

            if(
                !empty($result)
                &&
                $this->wordCount($section['content'])<50
            ){

                $last=count($result)-1;

                $result[$last]['content'].=
                    "\n\n".
                    $section['content'];

                continue;
            }


            $result[]=$section;
        }


        return $result;
    }


    protected function wordCount(string $text):int
    {
        if(blank($text))return 0;

        return count(
            preg_split(
                '/\s+/u',
                trim($text)
            )
        );
    }


    protected function level(string $line):int
    {
        if(
            preg_match(
                '/^(\d+(\.\d+)*)/',
                $line,
                $m
            )
        ){

            return substr_count($m[1],'.')+1;
        }

        return 1;
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
