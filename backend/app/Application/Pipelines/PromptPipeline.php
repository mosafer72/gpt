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
            $prompt
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
        PromptCollection $prompt
    ): void {


        $prompt->add(

            role:'system',

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
You are answering questions from uploaded documents.

Rules:
- The document context is the only source of truth.
- Do not use external knowledge.
- Do not guess missing information.
- If information is unavailable, answer:
"اطلاعات کافی در اسناد پیدا نشد."

Document Context:

TEXT;






        $count = 0;

        $words = 0;





        foreach (

            $context->documents as $document

        ) {



            if ($words >= 4000) {

                break;

            }






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





            if ($text === '') {

                continue;

            }







            $metadata =

                $data['metadata']
                ??
                [];







            $section =

                $metadata['section']
                ??

                $data['section']
                ??

                'general';







            $title =

                $metadata['title']
                ??

                'Unknown Document';








            $importance =

                $metadata['importance']
                ??

                '';






            $score =

                $data['final_score']
                ??

                $data['score']
                ??

                0;








            $count++;






            $content .= <<<TEXT


---------------------

Passage {$count}

Document:
{$title}

Section:
{$section}

Importance:
{$importance}

Score:
{$score}


Content:

{$text}


TEXT;





            $words += str_word_count($text);



        }






        if ($count > 0) {


            $prompt->add(

                role:'system',

                content:$content

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

            as $memory

        ) {



            $text =

                is_object($memory)

                ?

                $memory->text

                :

                ($memory['text'] ?? '');





            if ($text !== '') {


                $content .=

                    "- "

                    .

                    $text

                    .

                    "\n";


            }


        }






        $prompt->add(

            role:'system',

            content:$content

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





        $lastIndex =

            count($context->messages)-1;







        foreach (

            $context->messages

            as

            $index=>$message

        ) {




            if (

                $index === $lastIndex

                &&

                $message->role === 'user'

            ) {

                continue;

            }





            $prompt->add(

                role:$message->role,

                content:$message->content

            );



        }


    }









    protected function addCurrentMessage(

        PromptCollection $prompt,

        ChatContext $context

    ): void {


        if (
            blank($context->currentMessage)
        ) {

            return;

        }






        $prompt->add(

            role:'user',

            content:$context->currentMessage

        );


    }









    protected function normalizeMessages(

        array $messages

    ): array {



        $system=[];

        $conversation=[];






        foreach($messages as $message){



            if(
                ($message['role'] ?? '')
                ===
                'system'
            ){

                $system[]=$message;


            }else{


                $conversation[]=$message;


            }


        }






        return array_merge(

            $system,

            $conversation

        );


    }



}
