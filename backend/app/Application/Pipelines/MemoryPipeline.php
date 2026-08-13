<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Domain\Contracts\MemoryRetriever;
use App\Domain\Contracts\MemoryStore;
use App\Domain\Memory\DTOs\MemoryDTO;



class MemoryPipeline
{


    public function __construct(

        protected MemoryRetriever $retriever,

        protected MemoryStore $store

    ) {
    }







    public function retrieve(
        ChatContext $context
    ): ChatContext {


        $memories =

            $this->retrieveMemories(

                userId:
                $context->userId,


                query:
                $context->currentMessage

            );





        if (!empty($memories)) {


            $context->setMemories(
                $memories
            );


        }




        return $context;


    }








    public function retrieveMemories(

        string $userId,

        string $query,

        int $limit = 10

    ): array {


        if (
            blank($query)
        ) {

            return [];

        }






        $results =

            $this->retriever
                ->retrieve(

                    userId:
                    $userId,


                    query:
                    $query,


                    limit:
                    $limit

                );






        return collect($results)

            ->filter(

                function ($memory) {


                    return

                        !empty(
                        $memory->text
                    );


                }

            )

            ->map(

                function ($memory) use ($userId) {


                    return new MemoryDTO(


                        id:

                        (string)
                        (
                            $memory->id
                            ??
                            uniqid('memory_')
                        ),



                        user_id:

                        $userId,



                        text:

                        $memory->text,



                        importance:

                        (int)
                        (
                            $memory->importance
                            ??
                            1
                        ),



                        category:

                        $memory->category
                        ??
                        'general',



                        source_message:

                        $memory->source_message
                        ??
                        null,



                        embedding:

                        null,



                        last_used:

                        now()
                            ->toDateTimeString(),



                        created_at:

                        (string)
                        (
                            $memory->created_at
                            ??
                            now()
                                ->toDateTimeString()
                        )

                    );


                }

            )

            ->filter(

                function (MemoryDTO $memory) {


                    return $memory->importance >= 5;


                }

            )

            ->sortByDesc(

                fn(MemoryDTO $memory) =>

                $memory->importance

            )

            ->take($limit)

            ->values()

            ->toArray();


    }









    public function storeMessage(

        string $userId,

        string $message

    ): void {


        $memoryData =

            $this->extractMemory(
                $message
            );




        if (!$memoryData) {

            return;

        }





        $memory = new MemoryDTO(


            id:

            uniqid('memory_'),



            user_id:

            $userId,



            text:

            $memoryData['text'],



            importance:

            $memoryData['importance'],



            category:

            $memoryData['category'],



            source_message:

            $message,



            embedding:

            null,



            last_used:

            now()
                ->toDateTimeString(),



            created_at:

            now()
                ->toDateTimeString()


        );





        $this->store
            ->store(
                $memory
            );

    }









    protected function extractMemory(

        string $message

    ): ?array {



        $patterns = [


            '/من هستم (.+)/u' => 'profile',



            '/من (.+) دوست دارم/u' => 'preference',



            '/ترجیح میدم (.+)/u' => 'preference',



            '/همیشه (.+)/u' => 'instruction',



            '/یادم باشه (.+)/u' => 'profile',


        ];






        foreach ($patterns as $pattern => $category) {


            if (

                preg_match(

                    $pattern,

                    $message,

                    $matches

                )

            ) {


                $text =

                    trim(
                        $matches[1]
                    );



                if (
                    mb_strlen($text) < 3
                ) {

                    return null;

                }





                return [


                    'text' =>

                        $text,



                    'category' =>

                        $category,



                    'importance' =>

                        match ($category) {


                            'instruction' => 10,


                            'profile' => 8,


                            'preference' => 7,


                            default => 5

                        }


                ];


            }


        }




        return null;


    }


}
