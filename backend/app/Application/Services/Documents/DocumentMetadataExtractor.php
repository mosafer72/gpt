<?php

namespace App\Application\Services\Documents;


class DocumentMetadataExtractor
{


    public function extract(
        string $text,
        string $filename
    ): array
    {


        $lines =
            collect(
                explode("\n", $text)
            )
            ->map(
                fn($x)=>trim($x)
            )
            ->filter();



        return [

            'title' =>
                $this->extractTitle(
                    $lines,
                    $filename
                ),


            'authors' =>
                $this->extractAuthors(
                    $lines
                ),


            'year' =>
                $this->extractYear(
                    $text
                ),


            'type' =>
                'academic_paper',


            'language' =>
                $this->language(
                    $text
                )

        ];

    }







    protected function extractTitle(
        $lines,
        string $filename
    ): string
    {


        foreach(
            $lines->take(15)
            as $line
        ){

            if(
                mb_strlen($line) < 10
            ){
                continue;
            }



            if(
                $this->isNoise($line)
            ){
                continue;
            }



            /*
             اولین خط معتبر
             معمولا عنوان مقاله است
            */


            return $line;

        }




        return pathinfo(
            $filename,
            PATHINFO_FILENAME
        );


    }









    protected function extractAuthors(
        $lines
    ): array
    {


        $authors=[];



        foreach(
            $lines->take(20)
            as $line
        ){



            if(
                $this->isNoise($line)
            ){
                continue;
            }




            /*
              نویسنده ها معمولا
              قبل از Abstract هستند
              و شامل ایمیل/لینک نیستند
            */


            if(
                preg_match(
                    '/^[A-Z][a-z]+\s+[A-Z][a-z]+/',
                    $line
                )
            ){

                $authors[]=$line;

            }



        }



        return array_values(
            array_unique(
                $authors
            )
        );


    }









    protected function extractYear(
        string $text
    ): ?string
    {


        if(
            preg_match(
                '/\b(19|20)\d{2}\b/',
                $text,
                $match
            )
        ){

            return $match[0];

        }



        return null;


    }









    protected function language(
        string $text
    ): string
    {


        $fa =
            preg_match_all(
                '/[\x{0600}-\x{06FF}]/u',
                $text
            );



        return $fa > 20
            ? 'fa'
            : 'en';

    }









    protected function isNoise(
        string $line
    ): bool
    {


        if(
            str_contains(
                strtolower($line),
                'http'
            )
        ){

            return true;

        }




        if(
            str_contains(
                strtolower($line),
                '@'
            )
        ){

            return true;

        }




        if(
            preg_match(
                '/^[0-9\.\-\s]+$/',
                $line
            )
        ){

            return true;

        }




        if(
            mb_strlen($line)>150
        ){

            return true;

        }




        return false;

    }



}
