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



        /**
         * 1 - System Prompt
         */
        $this->addSystemPrompt(
            $prompt
        );



        /**
         * 2 - Memory Context
         */
        $this->addMemories(
            $prompt,
            $context
        );



        /**
         * 3 - Conversation History
         */
        $this->addConversation(
            $prompt,
            $context
        );



        /**
         * 4 - Current User Message
         */
        $this->addCurrentMessage(
            $prompt,
            $context
        );


        $context->setPrompt(
            $prompt->toArray()
        );



        return $context;

    }





    protected function addSystemPrompt(
        PromptCollection $prompt
    ): void {


        $prompt->add(
            role: 'system',
            content: SystemPrompt::default()
        );

    }






    protected function addMemories(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        if (empty($context->memories)) {
            return;
        }



        $memoryText = "User memories:\n";


        foreach ($context->memories as $memory) {

            $memoryText .=
                "- " . $memory->text . "\n";

        }



        $prompt->add(
            role: 'system',
            content: $memoryText
        );

    }





    protected function addConversation(
        PromptCollection $prompt,
        ChatContext $context
    ): void {

        $lastIndex = count($context->messages) - 1;

        foreach ($context->messages as $index => $message) {

            // آخرین پیام کاربر همان پیام فعلی است
            if (
                $index === $lastIndex
                && $message->role === 'user'
            ) {
                continue;
            }

            $prompt->add(
                role: $message->role,
                content: $message->content
            );
        }
    }

    protected function addCurrentMessage(
        PromptCollection $prompt,
        ChatContext $context
    ): void {


        $content = [];


        if (!empty($context->currentMessage)) {

            $content[] = [

                'type' => 'text',

                'text' => $context->currentMessage

            ];

        }


        if (
            !empty($context->metadata['attachments'])
        ) {

            foreach (
                $context->metadata['attachments']
                as $attachment
            ) {

                if (
                    ($attachment['type'] ?? null)
                    === 'image'
                ) {

                    $content[] = [

                        'type' => 'image_url',

                        'image_url' => [

                            'url' => $attachment['url']

                        ]

                    ];

                }

            }

        }


        // هیچ محتوایی نداریم
        if (empty($content)) {

            return;

        }


        // فقط متن
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


        // متن + عکس یا فقط عکس
        $prompt->add(

            'user',

            $content

        );

    }

}
