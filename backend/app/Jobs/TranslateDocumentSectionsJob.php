<?php

namespace App\Jobs;


use App\Models\Document;

use App\Infrastructure\Repositories\DocumentChunkRepository;

use App\Application\Services\Documents\SectionTranslationService;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;



class TranslateDocumentSectionsJob implements ShouldQueue
{


    use Queueable;



    public int $tries = 3;


    public int $timeout = 600;






    public function __construct(

        public Document $document

    ) {}









    public function handle(

        DocumentChunkRepository $chunkRepository,

        SectionTranslationService $translator

    ): void {


        try {


            /*
            |--------------------------------------------------------------------------
            | Load Stored Chunks
            |--------------------------------------------------------------------------
            */


            $chunks =

                $chunkRepository->findByDocument(

                    (string)
                    $this->document->id

                );






            if(empty($chunks)){


                throw new \Exception(

                    'No document chunks found'

                );


            }









            /*
            |--------------------------------------------------------------------------
            | Translate Chunks
            |--------------------------------------------------------------------------
            */


            foreach($chunks as $chunk){



                $content =

                    trim(

                        (string)
                        (
                            $chunk['content']
                            ??
                            ''
                        )

                    );






                if(blank($content)){


                    continue;


                }








                $translator->translate(


                    section:$chunk,



                    documentId:

                        (string)
                        $this->document->id,



                    workspaceId:

                        (string)
                        $this->document->workspace_id


                );



            }









            /*
            |--------------------------------------------------------------------------
            | Completed
            |--------------------------------------------------------------------------
            */


            $this->document->update([

                'translation_status'=>'completed'

            ]);








            Log::info(

                'DOCUMENT SECTION TRANSLATION COMPLETED',

                [

                    'document_id'=>

                        $this->document->id,


                    'chunks'=>

                        count($chunks)

                ]

            );







        }
        catch(\Throwable $e){



            Log::error(

                'DOCUMENT SECTION TRANSLATION FAILED',

                [

                    'document_id'=>

                        $this->document->id,


                    'error'=>

                        $e->getMessage()

                ]

            );






            $this->document->update([

                'translation_status'=>'failed'

            ]);





            throw $e;


        }



    }


}
