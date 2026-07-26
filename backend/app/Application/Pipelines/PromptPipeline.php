<?php

namespace App\Application\Pipelines;

use App\Application\Context\ChatContext;
use App\Application\Prompts\SystemPrompt;
use App\Domain\Prompt\PromptCollection;



class PromptPipeline
{


    public function build(
        ChatContext $context
    ): ChatContext {


        $prompt =
            new PromptCollection();




        $this->addSystemPrompt(
            $prompt,
            $context
        );




        $this->addDocuments(
            $prompt,
            $context
        );




        $this->addMemories(
            $prompt,
            $context
        );




        $this->addConversation(
            $prompt,
            $context
        );




        $this->addCurrentMessage(
            $prompt,
            $context
        );





        $context->setPrompt(

            $this->normalizeMessages(

                $prompt->toArray()

            )

        );




        return $context;

    }








    protected function addSystemPrompt(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        $prompt->add(

            role:

            'system',


            content:

            SystemPrompt::default()

        );


    }









    protected function addDocuments(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (
            empty($context->documents)
        ) {

            return;

        }



        $content = <<<TEXT
You are answering using uploaded document context.

Rules:
- Use only provided document passages.
- Do not invent information.
- If the answer is not available in the documents, say:
اطلاعات کافی در اسناد پیدا نشد.

Retrieved document passages:

TEXT;





        $hasContent = false;





        foreach (
            $context->documents
            as
            $index => $document
        ) {


            $data =
                is_array($document)

                ?

                $document

                :

                $document->toArray();





            $text =
                trim(
                    $data['content']
                    ??
                    ''
                );





            if (
                $text === ''
            ) {

                continue;

            }





            $hasContent = true;




            $metadata =
                $data['metadata']
                ??
                [];





            $title =
                $metadata['title']
                ??
                'Unknown';




            $section =
                $metadata['section']
                ??
                '';





            $number =
                $index + 1;





            $content .= <<<TEXT

--- Passage {$number} ---

Title:
{$title}

Section:
{$section}

Content:
{$text}


TEXT;


        }






        if ($hasContent) {


            $prompt->add(

                role:

                'system',


                content:

                $content

            );


        }


    }









    protected function addMemories(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (
            empty($context->memories)
        ) {

            return;

        }





        $content =
            "Relevant user context:\n\n";





        foreach (
            $context->memories
            as
            $memory
        ) {


            $content .=

                "- "

                .

                $memory->text

                .

                "\n";


        }





        $prompt->add(

            role:

            'system',


            content:

            $content

        );


    }









    protected function addConversation(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (
            empty($context->messages)
        ) {

            return;

        }




        $lastUserIndex =
            count($context->messages) - 1;





        foreach (
            $context->messages
            as
            $index => $message
        ) {



            if (

                $index === $lastUserIndex

                &&

                $message->role === 'user'

            ) {

                continue;

            }





            $prompt->add(

                role:

                $message->role,


                content:

                $message->content

            );


        }


    }









    protected function addCurrentMessage(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (
            empty($context->currentMessage)
        ) {

            return;

        }





        $content = [];





        $content[] = [

            'type' => 'text',

            'text' =>
                $context->currentMessage

        ];








        if (
            !empty(
                $context->metadata['attachments']
            )
        ) {


            foreach (
                $context->metadata['attachments']
                as
                $attachment
            ) {



                if (
                    ($attachment['type'] ?? null)
                    ===
                    'image'
                ) {



                    $content[] = [

                        'type' =>
                        'image_url',


                        'image_url' => [

                            'url' =>
                            $attachment['url']

                        ]

                    ];



                }


            }


        }







        if (
            count($content) === 1
        ) {



            $prompt->add(

                role:

                'user',


                content:

                $context->currentMessage

            );


            return;


        }






        $prompt->add(

            role:

            'user',


            content:

            $content

        );


    }









    protected function normalizeMessages(
        array $messages
    ): array {


        $system = [];

        $conversation = [];





        foreach (
            $messages
            as
            $message
        ) {



            if (
                ($message['role'] ?? null)
                ===
                'system'
            ) {


                $system[] = $message;


            } else {


                $conversation[] = $message;


            }


        }





        return array_merge(

            $system,

            $conversation

        );


    }


}
