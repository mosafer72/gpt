<?php

namespace App\Application\Services\Documents;


use App\Models\Document;
use App\Infrastructure\Repositories\DocumentStructureRepository;
use App\Infrastructure\Repositories\DocumentSectionPlanRepository;
use App\Infrastructure\Repositories\DocumentSectionTranslationRepository;



class DocumentOverviewService
{


    public function __construct(

        protected DocumentStructureRepository $structureRepository,

        protected DocumentSectionPlanRepository $sectionRepository,

        protected DocumentSectionTranslationRepository $translationRepository

    ) {
    }






    public function overview(
        Document $document
    ): array {



        /*
        |--------------------------------------------------------------------------
        | Structure
        |--------------------------------------------------------------------------
        */


        $structure =
            $this->structureRepository
                ->findByDocument(

                    (string) $document->id

                );






        /*
        |--------------------------------------------------------------------------
        | AI Planned Sections
        |--------------------------------------------------------------------------
        */


        $sections =
            $this->sectionRepository
                ->findTopSections(

                    workspaceId:
                    (string) $document->workspace_id,

                    documentId:
                    (string) $document->id,

                    limit:20

                );




        $sections =
            $this->formatSections(
                $sections
            );






        /*
        |--------------------------------------------------------------------------
        | Persian Translations
        |--------------------------------------------------------------------------
        */


        $translations =
            $this->translationRepository
                ->getByDocument(

                    documentId:
                    (string) $document->id,


                    workspaceId:
                    (string) $document->workspace_id

                );





        $sections =
            $this->mergeTranslations(

                $sections,

                $translations

            );








        return [

            'document'=>[

                'id'=>
                    $document->id,


                'name'=>
                    $document->name,


                'path'=>
                    $document->path,


                'status'=>
                    $document->status,


                'size'=>
                    $document->size,


                'mime_type'=>
                    $document->mime_type,


                'created_at'=>
                    $document->created_at,

            ],




            'structure'=>
                $this->formatStructure(
                    $structure
                ),




            'sections'=>
                $sections,





            'stats'=>[

                'status'=>
                    $document->status

            ]

        ];

    }









    protected function mergeTranslations(

        array $sections,

        array $translations

    ): array {



        foreach($sections as &$section){



            $section['translation'] = [

                'status'=>'pending',

                'title_fa'=>null,

                'summary_fa'=>null,

                'translated_content'=>null

            ];





            foreach($translations as $translation){



                if(

                    ($translation['section'] ?? null)

                    ===

                    ($section['section'] ?? null)

                ){



                    $section['translation'] = [

                        'status'=>'completed',


                        'title_fa'=>
                            $translation['title']
                            ?? null,


                        'summary_fa'=>
                            $translation['summary_fa']
                            ?? null,


                        'translated_content'=>
                            $translation['translated_content']
                            ?? null,


                    ];



                    break;

                }


            }



        }



        return $sections;

    }









    protected function formatStructure(

        $data

    ): array {



        if(empty($data)){

            return [];

        }





        if(isset($data[0]['result'])){


            return
                $data[0]['result'][0]
                ??
                [];

        }





        return $data[0] ?? [];

    }









    protected function formatSections(

        $data

    ): array {



        if(empty($data)){

            return [];

        }




        if(isset($data[0]['result'])){


            $data =
                $data[0]['result'];

        }





        $result=[];





        foreach($data as $item){



            /*
            |--------------------------------------------------------------------------
            | Surreal nested response
            |--------------------------------------------------------------------------
            */


            if(

                isset($item[0])

                &&

                is_array($item[0])

            ){

                $item =
                    $item[0];

            }





            if(

                is_array($item)

                &&

                isset($item['section'])

            ){

                $result[] =
                    $item;

            }


        }





        return $result;

    }



}
