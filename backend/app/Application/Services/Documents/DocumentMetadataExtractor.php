<?php

namespace App\Application\Services\Documents;


class DocumentMetadataExtractor
{


    public function extract(
        string $text,
        string $filename
    ): array {


        $lines =
            collect(
                explode("\n", $text)
            )
            ->map(
                fn($line) => trim($line)
            )
            ->filter();



        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */


        $title =
            $lines
                ->first();



        if (!$title) {

            $title = pathinfo(
                $filename,
                PATHINFO_FILENAME
            );

        }





        /*
        |--------------------------------------------------------------------------
        | Abstract
        |--------------------------------------------------------------------------
        */


        $abstract = '';



        $pattern =
            '/abstract\s*(.*?)(introduction|1\. introduction)/is';



        if (
            preg_match(
                $pattern,
                $text,
                $matches
            )
        ) {


            $abstract =
                trim(
                    $matches[1]
                );

        }





        /*
        |--------------------------------------------------------------------------
        | Year
        |--------------------------------------------------------------------------
        */


        preg_match(
            '/\b(19|20)\d{2}\b/',
            $text,
            $yearMatch
        );



        $year =
            $yearMatch[0]
            ??
            null;






        return [

            'title' =>
                $title,


            'abstract' =>
                mb_substr(
                    $abstract,
                    0,
                    2000
                ),


            'authors' =>
                [],


            'year' =>
                $year,


            'type' =>
                'academic_paper'


        ];

    }

}
