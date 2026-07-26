<?php

namespace App\Jobs;


use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

use App\Application\Services\Documents\SectionTranslationService;
use App\Application\Services\Documents\PdfExtractor;
use App\Application\Services\Documents\Chunker;



class TranslateDocumentSectionsJob implements ShouldQueue
{


    use Queueable;



    public int $tries = 3;


    public int $timeout = 600;






    public function __construct(

        public Document $document

    ) {

    }








    public function handle(

        PdfExtractor $extractor,

        Chunker $chunker,

        SectionTranslationService $translator

    ): void {


        try {


            /*
            |--------------------------------------------------------------------------
            | Extract Document
            |--------------------------------------------------------------------------
            */


            $text =

                $extractor->extract(

                    $this->document->path

                );



            if (blank($text)) {


                throw new \Exception(
                    'Document text empty'
                );


            }







            /*
            |--------------------------------------------------------------------------
            | Generate Sections
            |--------------------------------------------------------------------------
            */


            $chunks =

                $chunker->split(

                    $text

                );



            unset($text);




            if (empty($chunks)) {


                throw new \Exception(
                    'No sections found'
                );


            }







            /*
            |--------------------------------------------------------------------------
            | Translate Sections
            |--------------------------------------------------------------------------
            */


            foreach ($chunks as $section) {



                if (
                    empty($section['content'])
                ) {

                    continue;

                }





                $translator->translate(


                    section: $section,



                    documentId:

                    (string)
                    $this->document->id,



                    workspaceId:

                    (string)
                    $this->document->workspace_id


                );



            }







            Log::info(

                'DOCUMENT SECTION TRANSLATION COMPLETED',

                [

                    'document_id' =>
                        $this->document->id,


                    'sections' =>
                        count($chunks)

                ]

            );







        } catch (\Throwable $e) {



            Log::error(

                'DOCUMENT SECTION TRANSLATION FAILED',

                [

                    'document_id' =>
                        $this->document->id,


                    'error' =>
                        $e->getMessage()

                ]

            );



            throw $e;


        }


    }



}
