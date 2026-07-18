<?php

namespace App\Application\Pipelines;


use App\Application\Context\ChatContext;
use App\Application\Prompts\SystemPrompt;
use App\Domain\Prompt\PromptCollection;
use App\Models\Chat;
use App\Models\Paper;



class PromptPipeline
{


    public function build(
        ChatContext $context
    ): ChatContext {


        $prompt = new PromptCollection();



        $this->addSystemPrompt(
            $prompt
        );


        /**
         * 2 - Paper Context
         */
        $this->addPaperContext(
            $prompt,
            $context
        );


        /**
         * 3 - Memory Context
         */
        $this->addMemories(
            $prompt,
            $context
        );


        /**
         * 4 - Conversation History
         */
        $this->addConversation(
            $prompt,
            $context
        );


        /**
         * 5 - Current User Message
         */
        $this->addCurrentMessage(
            $prompt,
            $context
        );

        $messages = $prompt->toArray();


        $systemMessages = [];
        $normalMessages = [];


        foreach ($messages as $message) {

            if (
                $message['role'] === 'system'
            ) {

                $systemMessages[] = $message;

            } else {

                $normalMessages[] = $message;

            }

        }


        $context->setPrompt(
            array_merge(
                $systemMessages,
                $normalMessages
            )
        );
        $finalMessages = array_merge($systemMessages, $normalMessages);

        $context->setPrompt($finalMessages);

        \Log::info('FINAL PROMPT ORDER', [
            'messages' => $finalMessages
        ]);


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
    protected function addPaperContext(
        PromptCollection $prompt,
        ChatContext $context
    ): void {

        // اول از conversation بخوان، اگر نبود از metadata
        $chat = Chat::find($context->conversationId);

        if (!$chat) {
            return;
        }

        $paper = $chat->papers()
            ->orderByPivot('created_at', 'desc')
            ->first();

        if (!$paper || empty($paper->extracted_text)) {
            return;
        }

        if (!$paper || empty($paper->extracted_text)) {
            return;
        }

        $paperText = mb_substr(
            $paper->extracted_text,
            0,
            12000
        );

        $prompt->add(
            role: 'system',
            content:
            "کاربر یک مقاله علمی آپلود کرده است. " .
            "در پاسخ‌های بعدی از محتوای این مقاله استفاده کن.\n\n" .
            "عنوان مقاله: {$paper->title}\n\n" .
            "متن مقاله:\n{$paperText}"
        );

        \Log::info('PAPER PROMPT', [
            'paper_id' => $paper->id,
            'title' => $paper->title,
            'text_length' => mb_strlen($paper->extracted_text),
        ]);
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
