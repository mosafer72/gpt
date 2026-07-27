<?php

namespace App\Jobs;


use App\Application\Services\Ai\EmbeddingService;
use App\Application\Services\Documents\Chunker;
use App\Application\Services\Documents\DocumentMetadataExtractor;
use App\Application\Services\Documents\DocumentSectionDetector;
use App\Application\Services\Documents\DocumentStructureBuilder;
use App\Application\Services\Documents\PdfExtractor;

use App\Infrastructure\Repositories\DocumentChunkRepository;
use App\Infrastructure\Repositories\DocumentVectorRepository;

use App\Models\Document;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;



class ProcessDocumentJob implements ShouldQueue
{


    use Queueable;



    public int $tries = 3;


    public int $timeout = 600;






    public function __construct(
        public Document $document
    ) {}









    public function handle(

        PdfExtractor $extractor,

        DocumentSectionDetector $sectionDetector,

        DocumentMetadataExtractor $metadataExtractor,

        DocumentStructureBuilder $structureBuilder,

        Chunker $chunker,

        EmbeddingService $embeddingService,

        DocumentChunkRepository $chunkRepository,

        DocumentVectorRepository $vectorRepository


    ): void {



        ini_set(
            'memory_limit',
            '512M'
        );





        try {


            $this->document->update([

                'status'=>'processing',

                'error'=>null

            ]);






            /*
            |--------------------------------------------------------------------------
            | Extract Text
            |--------------------------------------------------------------------------
            */


            $text =
                $extractor->extract(
                    $this->document->path
                );




            if(blank($text)) {

                throw new \Exception(
                    'Document text empty'
                );

            }








            /*
            |--------------------------------------------------------------------------
            | Sections
            |--------------------------------------------------------------------------
            */


            $sections =
                $sectionDetector->detect(
                    $text
                );




            if(empty($sections)) {

                throw new \Exception(
                    'No sections detected'
                );

            }









            /*
            |--------------------------------------------------------------------------
            | Metadata
            |--------------------------------------------------------------------------
            */


            $paperMetadata =
                $metadataExtractor->extract(

                    $text,

                    $this->document->name

                );





            $this->document->update([

                'metadata'=>$paperMetadata

            ]);









            /*
            |--------------------------------------------------------------------------
            | Structure
            |--------------------------------------------------------------------------
            */


            $structureBuilder->build(

                document:$this->document,

                sections:$sections

            );









            /*
            |--------------------------------------------------------------------------
            | Chunk
            |--------------------------------------------------------------------------
            */


            $chunks =
                $chunker->split(
                    $text
                );




            unset($text);





            if(empty($chunks)) {

                throw new \Exception(
                    'No chunks generated'
                );

            }







            $counter = 0;








            foreach($chunks as $chunk) {



                $content =
                    trim(

                        (string)
                        (
                            $chunk['content']
                            ??
                            ''
                        )

                    );





                if(blank($content)) {

                    continue;

                }









                $metadata = [


                    'filename'=>
                        $this->document->name,



                    'type'=>
                        $paperMetadata['type']
                        ??
                        'academic_paper',



                    'title'=>
                        $paperMetadata['title']
                        ??
                        '',



                    'abstract'=>
                        $paperMetadata['abstract']
                        ??
                        '',



                    'authors'=>
                        $paperMetadata['authors']
                        ??
                        [],



                    'year'=>
                        $paperMetadata['year']
                        ??
                        '',



                    'section'=>
                        strtolower(

                            $chunk['section']
                            ??
                            'general'

                        ),



                    'section_title'=>
                        $chunk['section_title']
                        ??
                        '',



                    'heading_path'=>
                        $chunk['heading_path']
                        ??
                        [],



                    'importance'=>
                        $chunk['importance']
                        ??
                        'medium',



                    'chunk_index'=>
                        (int)
                        (
                            $chunk['chunk_index']
                            ??
                            0
                        ),



                    'word_count'=>
                        (int)
                        (
                            $chunk['word_count']
                            ??
                            0
                        )

                ];









                /*
                |--------------------------------------------------------------------------
                | Save Chunk
                |--------------------------------------------------------------------------
                */


                $savedChunk =

                    $chunkRepository->store([


                        'document_id'=>
                            (string)
                            $this->document->id,


                        'workspace_id'=>
                            (string)
                            $this->document->workspace_id,


                        'chunk_index'=>
                            $metadata['chunk_index'],


                        'content'=>
                            $content,


                        'tokens'=>
                            (int)
                            (
                                $chunk['tokens']
                                ??
                                0
                            ),


                        'metadata'=>
                            $metadata


                    ]);









                $chunkId =
                    (string)
                    (
                        $savedChunk['id']
                        ??
                        ''
                    );







                if(blank($chunkId)) {


                    Log::warning(
                        'CHUNK ID EMPTY'
                    );


                    continue;

                }









                /*
                |--------------------------------------------------------------------------
                | Embedding
                |--------------------------------------------------------------------------
                */


                $embeddingText =

                    "Document Type:
Academic Paper

Title:
"

                    .

                    (
                        $paperMetadata['title']
                        ??
                        $this->document->name
                    )

                    .

                    "

Section:
"

                    .

                    $metadata['section']

                    .

                    "

Content:
"

                    .

                    $content;









                try {


                    $embedding =

                        $embeddingService
                            ->embedDocument(
                                $embeddingText
                            );


                }
                catch(\Throwable $e) {


                    Log::warning(

                        'EMBEDDING FAILED',

                        [

                            'document_id'=>$this->document->id,

                            'error'=>$e->getMessage()

                        ]

                    );


                    continue;

                }







                if(empty($embedding)) {

                    continue;

                }









                /*
                |--------------------------------------------------------------------------
                | Save Vector
                |--------------------------------------------------------------------------
                */


                $vectorRepository->store([


                    'document_id'=>
                        $this->document->id,


                    'workspace_id'=>
                        $this->document->workspace_id,


                    'chunk_id'=>
                        $chunkId,


                    'content'=>
                        $content,


                    'embedding'=>
                        $embedding,


                    'metadata'=>
                        $metadata


                ]);







                $counter++;




                if($counter % 25 === 0){

                    gc_collect_cycles();

                }



            }









            /*
            |--------------------------------------------------------------------------
            | Translation
            |--------------------------------------------------------------------------
            */


            $this->document->update([

                'status'=>'processed'

            ]);





            TranslateDocumentSectionsJob::dispatch(
                $this->document
            );









            Log::info(

                'DOCUMENT PROCESSED',

                [

                    'document_id'=>$this->document->id,

                    'chunks'=>count($chunks)

                ]

            );







        }
        catch(\Throwable $e) {



            Log::error(

                'DOCUMENT PROCESSING FAILED',

                [

                    'document_id'=>$this->document->id,

                    'error'=>$e->getMessage()

                ]

            );






            $this->document->update([

                'status'=>'failed',

                'error'=>$e->getMessage()

            ]);





            throw $e;


        }



    }


}
