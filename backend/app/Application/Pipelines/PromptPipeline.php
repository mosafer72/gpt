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


        $prompt = new PromptCollection();



        /*
        |--------------------------------------------------------------------------
        | 1 - System Instruction
        |--------------------------------------------------------------------------
        */

        $this->addSystemPrompt(
            $prompt
        );



        /*
        |--------------------------------------------------------------------------
        | 2 - Document RAG Context
        |--------------------------------------------------------------------------
        */

        $this->addDocuments(
            $prompt,
            $context
        );



        /*
        |--------------------------------------------------------------------------
        | 3 - Memory Context
        |--------------------------------------------------------------------------
        */

        $this->addMemories(
            $prompt,
            $context
        );



        /*
        |--------------------------------------------------------------------------
        | 4 - Conversation History
        |--------------------------------------------------------------------------
        */

        $this->addConversation(
            $prompt,
            $context
        );



        /*
        |--------------------------------------------------------------------------
        | 5 - Current User Message
        |--------------------------------------------------------------------------
        */

        $this->addCurrentMessage(
            $prompt,
            $context
        );



        /*
        |--------------------------------------------------------------------------
        | Ensure system messages are first
        |--------------------------------------------------------------------------
        */

        $messages =
            $prompt->toArray();



        $system = [];

        $normal = [];



        foreach ($messages as $message) {


            if (
                ($message['role'] ?? null)
                ===
                'system'
            ) {

                $system[] = $message;

            } else {

                $normal[] = $message;

            }

        }



        $context->setPrompt(

            array_merge(
                $system,
                $normal
            )

        );



        return $context;

    }







    protected function addSystemPrompt(
        PromptCollection $prompt
    ): void {


        $prompt->add(

            role: 'system',

            content:
            SystemPrompt::default()

        );

    }







    protected function addDocuments(
        PromptCollection $prompt,
        ChatContext $context
    ): void {

        \Log::info('PROMPT DOCUMENT CHECK', [
            'documents_count' => count($context->documents)
        ]);
        if (empty($context->documents)) {

            return;

        }



        $text =
            <<<PROMPT
You are a document-based AI assistant.

The following passages are retrieved from uploaded documents.

Rules:
- Use only these passages as evidence.
- Do not invent information.
- If information is missing, say that the documents do not contain enough information.
- Answer clearly and concisely.

Retrieved passages:
PROMPT;



        $valid = false;



        foreach (
            $context->documents
            as
            $index => $document
        ) {


            $content =
                is_array($document)

                ?
                ($document['content'] ?? '')

                :

                ($document->content ?? '');



            if (
                trim($content) === ''
            ) {

                continue;

            }



            $valid = true;



            $number =
                $index + 1;



            $text .=
                "--- Passage {$number} ---\n"
                .
                $content
                .
                "\n\n";

        }




        if ($valid) {


            $prompt->add(

                role: 'system',

                content: $text

            );

        }

    }







    protected function addMemories(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (empty($context->memories)) {

            return;

        }



        $memory =
            "Relevant user memories:\n\n";



        foreach (
            $context->memories
            as
            $item
        ) {


            $memory .=
                "- "
                .
                $item->text
                .
                "\n";

        }



        $prompt->add(

            role: 'system',

            content: $memory

        );

    }







    protected function addConversation(
        PromptCollection $prompt,
        ChatContext $context
    ): void {



        $last =
            count($context->messages) - 1;



        foreach (
            $context->messages
            as
            $index => $message
        ) {


            /*
            آخرین user message
            همان پیام فعلی است
            */

            if (
                $index === $last
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



        $content = [];



        if (
            !empty($context->currentMessage)
        ) {

            $content[] = [

                'type' => 'text',

                'text' =>
                    $context->currentMessage

            ];

        }





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

                        'type' => 'image_url',

                        'image_url' => [

                            'url' =>
                                $attachment['url']

                        ]

                    ];

                }

            }

        }





        if (empty($content)) {

            return;

        }





        if (
            count($content) === 1
            &&
            $content[0]['type'] === 'text'
        ) {


            $prompt->add(

                'user',

                $content[0]['text']

            );


            return;

        }





        $prompt->add(

            'user',

            $content

        );

    }


}
