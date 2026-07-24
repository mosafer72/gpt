<?php

namespace App\Application\Services\Documents;


class Chunker
{

    /**
     * Academic paper sections
     */
    protected array $sections = [

        'abstract',

        'introduction',

        'background',

        'related work',

        'literature review',

        'preliminaries',

        'method',

        'methods',

        'methodology',

        'approach',

        'model',

        'architecture',

        'implementation',

        'experiment',

        'experiments',

        'evaluation',

        'results',

        'discussion',

        'conclusion',

        'future work',

        'limitations',

        'references',

        'acknowledgement',

        'acknowledgment',

        'appendix',

    ];



    /**
     * Never embed these sections
     */
    protected array $ignoredSections = [

        'references',

        'acknowledgement',

        'acknowledgment',

        'appendix',

    ];



    /**
     * Dynamic academic chunk sizes
     */
    protected array $sectionChunkSizes = [

        'abstract' => 180,

        'introduction' => 300,

        'background' => 350,

        'related work' => 450,

        'literature review' => 450,

        'preliminaries' => 400,

        'method' => 450,

        'methods' => 450,

        'methodology' => 500,

        'approach' => 450,

        'model' => 500,

        'architecture' => 500,

        'implementation' => 400,

        'experiment' => 350,

        'experiments' => 350,

        'evaluation' => 350,

        'results' => 300,

        'discussion' => 300,

        'conclusion' => 220,

        'future work' => 200,

    ];




    /**
     * Split academic document
     */
    public function split(
        string $text,
        int $defaultChunkSize = 300
    ): array {


        $text = str_replace("\r", '', trim($text));


        if (empty($text)) {

            return [];

        }



        $sections =
            $this->extractSections($text);



        $chunks = [];

        $globalIndex = 1;



        foreach ($sections as $section) {



            if (
                in_array(
                    $section['normalized_name'],
                    $this->ignoredSections,
                    true
                )
            ) {

                continue;

            }



            $chunkSize =
                $this->sectionChunkSizes[
                    $section['normalized_name']
                ]
                ??
                $defaultChunkSize;



            $overlap =
                (int) ($chunkSize * 0.15);



            $sectionChunks =
                $this->chunkText(
                    $section['text'],
                    $chunkSize,
                    $overlap
                );




            foreach ($sectionChunks as $index => $content) {


                $wordCount =
                    count(
                        preg_split(
                            '/\s+/u',
                            trim($content)
                        )
                    );



                $chunks[] = [

                    'section'
                    =>
                        (string)
                        ($section['normalized_name'] ?? ''),


                    'section_title'
                    =>
                        trim(
                            (string)
                            ($section['title'] ?? '')
                        ),



                    'heading_path'
                    =>
                        [
                            $section['title']
                        ],



                    'chunk_index'
                    =>
                        $globalIndex++,



                    'section_chunk_index'
                    =>
                        $index + 1,



                    'word_count'
                    =>
                        $wordCount,



                    'importance'
                    =>
                        $this->sectionImportance(
                            $section['normalized_name']
                        ),



                    'content'
                    =>
                        trim(
                            is_string($content)
                            ? $content
                            : json_encode(
                                $content,
                                JSON_UNESCAPED_UNICODE
                            )
                        ),


                ];

            }

        }


        return $chunks;

    }






    /**
     * Extract sections from paper
     */
    protected function extractSections(
        string $text
    ): array {



        $lines =
            explode(
                "\n",
                $text
            );



        $sections = [];



        $currentSection =
            'abstract';



        $currentTitle =
            'Abstract';



        $buffer = [];




        $pattern =
            '/^(?:(section|chapter)\s+[\dIVXLCDM]+[:\.\-]?\s*)?
            ([\d\.]+\s*)?
            ('
            .
            implode('|', $this->sections)
            .
            ')
            (\s*[:\.-].*)?
            $/ix';




        foreach ($lines as $line) {



            $trim =
                trim($line);



            if ($trim === '') {


                $buffer[] = '';

                continue;

            }




            $words =
                count(
                    preg_split(
                        '/\s+/u',
                        $trim
                    )
                );



            if (
                $words <= 8 &&
                preg_match(
                    $pattern,
                    $trim,
                    $matches
                )
            ) {


                if (!empty(array_filter($buffer))) {



                    $sections[] = [

                        'normalized_name'
                        =>
                            strtolower(
                                trim(
                                    $currentSection
                                )
                            ),


                        'title'
                        =>
                            $currentTitle,


                        'text'
                        =>
                            implode(
                                "\n",
                                $buffer
                            ),

                    ];


                }



                $currentSection =
                    strtolower(
                        trim(
                            $matches[3]
                        )
                    );



                $currentTitle =
                    $trim;



                $buffer = [];



                continue;

            }



            $buffer[] = $line;


        }




        if (!empty(array_filter($buffer))) {


            $sections[] = [


                'normalized_name'
                =>
                    strtolower(
                        $currentSection
                    ),


                'title'
                =>
                    $currentTitle,


                'text'
                =>
                    implode(
                        "\n",
                        $buffer
                    ),

            ];

        }



        return $sections;

    }







    /**
     * Sentence based chunking
     */
    protected function chunkText(
        string $text,
        int $chunkSize,
        int $overlap
    ): array {



        $sentences =
            preg_split(
                '/(?<=[.!?])\s+/u',
                $text,
                -1,
                PREG_SPLIT_NO_EMPTY
            );



        $chunks = [];

        $current = [];



        foreach ($sentences as $sentence) {



            $words =
                preg_split(
                    '/\s+/u',
                    trim($sentence)
                );



            if (empty($words)) {

                continue;

            }




            if (
                count($current)
                +
                count($words)
                >
                $chunkSize
            ) {



                if (!empty($current)) {


                    $chunks[] =
                        implode(
                            ' ',
                            $current
                        );



                    $current =
                        array_slice(
                            $current,
                            -$overlap
                        );


                }


            }



            $current =
                array_merge(
                    $current,
                    $words
                );

        }



        if (!empty($current)) {


            $chunks[] =
                implode(
                    ' ',
                    $current
                );


        }



        return array_values(
            array_filter($chunks)
        );

    }






    /**
     * Academic importance score
     */
    protected function sectionImportance(
        string $section
    ): string {


        return match ($section) {


            'method',
            'methods',
            'methodology',
            'architecture',
            'model'
            =>
            'very_high',



            'abstract',
            'introduction',
            'results',
            'evaluation',
            'conclusion'
            =>
            'high',



            default
            =>
            'medium',

        };


    }


}
