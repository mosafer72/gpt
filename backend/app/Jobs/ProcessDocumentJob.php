<?php

namespace App\Jobs;


use App\Application\Services\Ai\EmbeddingService;
use App\Application\Services\Documents\Chunker;
use App\Application\Services\Documents\DocumentStructureBuilder;
use App\Application\Services\Documents\PdfExtractor;
use App\Application\Services\Documents\SectionPlannerService;
use App\Infrastructure\Repositories\DocumentVectorRepository;
use App\Models\Document;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Application\Services\Documents\DocumentMetadataExtractor;



class ProcessDocumentJob implements ShouldQueue
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

        EmbeddingService $embeddingService,

        DocumentVectorRepository $vectorRepository,

        SectionPlannerService $sectionPlanner,

        DocumentStructureBuilder $structureBuilder,

        DocumentMetadataExtractor $metadataExtractor,

    ): void {


        ini_set(
            'memory_limit',
            '512M'
        );




        try {



            $this->document->update([

                'status' => 'processing',

                'error' => null

            ]);






            /*
            |--------------------------------------------------------------------------
            | Extract
            |--------------------------------------------------------------------------
            */


            $text =
                $extractor->extract(
                    $this->document->path
                );


            $paperMetadata =
                $metadataExtractor->extract(

                    $text,

                    $this->document->name

                );



            $this->document->update([

                'metadata' =>
                    $paperMetadata,

            ]);

            if (blank($text)) {


                throw new \Exception(
                    'Document text empty'
                );


            }







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




            if (empty($chunks)) {


                throw new \Exception(
                    'No chunks generated'
                );


            }




            Log::info(
                'DOCUMENT CHUNK CREATED',
                [

                    'document_id' => $this->document->id,

                    'count' => count($chunks)

                ]
            );








            /*
            |--------------------------------------------------------------------------
            | Structure
            |--------------------------------------------------------------------------
            */


            try {


                $structureBuilder->build(

                    document: $this->document,

                    sections: $chunks

                );



            } catch (\Throwable $e) {


                Log::warning(
                    'STRUCTURE FAILED',
                    [

                        'document_id' => $this->document->id,

                        'error' => $e->getMessage()

                    ]
                );


            }









            /*
            |--------------------------------------------------------------------------
            | Planner
            |--------------------------------------------------------------------------
            */


            $normalizeSection = function ($value) {


                return strtolower(

                    preg_replace(

                        '/[^a-z0-9]/',

                        '',

                        trim((string) $value)

                    )

                );


            };




            $probabilityMap = [];





            try {


                $plans =
                    $sectionPlanner->analyze(

                        documentId:

                        (string)
                        $this->document->id,


                        workspaceId:

                        (string)
                        $this->document->workspace_id,


                        sections: $chunks

                    );





                if (is_array($plans)) {


                    foreach ($plans as $plan) {


                        if (empty($plan['section'])) {

                            continue;

                        }



                        $probabilityMap[

                            $normalizeSection(
                                $plan['section']
                            )

                        ] =

                            (float)
                            (
                                $plan['answer_probability']
                                ??
                                0
                            );


                    }


                }




            } catch (\Throwable $e) {


                Log::warning(
                    'PLANNER FAILED',
                    [

                        'document_id' => $this->document->id,

                        'error' => $e->getMessage()

                    ]
                );


            }












            /*
            |--------------------------------------------------------------------------
            | Embedding
            |--------------------------------------------------------------------------
            */


            $batchSize = 20;


            $vectors = [];





            foreach (
                array_chunk(
                    $chunks,
                    $batchSize
                )
                as $batch
            ) {



                foreach ($batch as $chunk) {



                    $content =
                        trim(
                            (string)
                            (
                                $chunk['content']
                                ??
                                ''
                            )
                        );



                    if (blank($content)) {

                        continue;

                    }






                    $section =
                        strtolower(

                            trim(

                                (string)
                                (
                                    $chunk['section']
                                    ??
                                    ''
                                )

                            )

                        );





                    $sectionProbability =

                        $probabilityMap[

                            $normalizeSection(
                                $section
                            )

                        ]
                        ??
                        0;






                    $embeddingText =

                        "Document Type:
Academic Paper

Section:
{$section}

Probability:
{$sectionProbability}

Title:
"
                        .
                        (
                            $chunk['section_title']
                            ??
                            ''
                        )
                        .

                        "

Content:
{$content}";







                    try {


                        $embedding =

                            $embeddingService
                                ->embedDocument(
                                    $embeddingText
                                );



                    } catch (\Throwable $e) {


                        Log::warning(
                            'EMBEDDING FAILED',
                            [

                                'document_id' => $this->document->id,

                                'error' => $e->getMessage()

                            ]
                        );


                        continue;


                    }






                    if (empty($embedding)) {


                        continue;

                    }







                    $vectors[] = [


                        'document_id' =>
                            (string)
                            $this->document->id,


                        'workspace_id' =>
                            (string)
                            $this->document->workspace_id,


                        'chunk_id' =>
                            uniqid('chunk_'),


                        'content' => $content,


                        'embedding' => $embedding,



                        'metadata' => [
                            'filename' =>
                                $this->document->name,


                            'type' =>
                                $paperMetadata['type'],


                            'title' =>
                                $paperMetadata['title'],


                            'abstract' =>
                                $paperMetadata['abstract'],


                            'authors' =>
                                $paperMetadata['authors'],


                            'year' =>
                                $paperMetadata['year'],

                            'section' =>
                                $section,


                            'section_probability' =>
                                $sectionProbability,


                            'section_title' =>
                                $chunk['section_title']
                                ??
                                '',


                            'heading_path' =>
                                $chunk['heading_path']
                                ??
                                [],


                            'importance' =>
                                $chunk['importance']
                                ??
                                'medium',


                            'chunk_index' =>
                                (int)
                                (
                                    $chunk['chunk_index']
                                    ??
                                    0
                                ),


                            'section_chunk_index' =>
                                (int)
                                (
                                    $chunk['section_chunk_index']
                                    ??
                                    0
                                ),


                            'word_count' =>
                                (int)
                                (
                                    $chunk['word_count']
                                    ??
                                    0
                                )

                        ]

                    ];




                }







                if (!empty($vectors)) {


                    $vectorRepository
                        ->storeBulk(
                            $vectors
                        );


                }



                $vectors = [];


                gc_collect_cycles();


            }








            /*
            |--------------------------------------------------------------------------
            | Translation Queue
            |--------------------------------------------------------------------------
            */


            TranslateDocumentSectionsJob::dispatch(
                $this->document
            );







            /*
            |--------------------------------------------------------------------------
            | Complete
            |--------------------------------------------------------------------------
            */


            $this->document->update([

                'status' => 'completed'

            ]);





            Log::info(
                'DOCUMENT COMPLETED',
                [

                    'document_id' => $this->document->id,

                    'chunks' => count($chunks)

                ]
            );







        } catch (\Throwable $e) {



            Log::error(
                'DOCUMENT PROCESSING FAILED',
                [

                    'document_id' => $this->document->id,

                    'error' => $e->getMessage()

                ]
            );




            $this->document->update([

                'status' => 'failed',

                'error' => $e->getMessage()

            ]);



            throw $e;


        }


    }


}
