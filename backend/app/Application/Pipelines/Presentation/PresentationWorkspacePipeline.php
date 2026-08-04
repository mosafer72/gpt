<?php

namespace App\Application\Pipelines\Presentation;

use App\Application\Context\ChatContext;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Infrastructure\LLM\GapGPTProvider;
use Illuminate\Support\Facades\Log;

class PresentationWorkspacePipeline implements WorkspacePipeline
{
    public function __construct(
        protected GapGPTProvider $llm,
    ) {
    }

    public function stream(
        ChatContext $context,
        callable $send
    ): void {
    }


    public function handle(ChatContext $context): ChatContext
    {
        $lastError = null;

        foreach ($this->availableModels() as $model) {

            try {

                Log::info(
                    "TRY PRESENTATION MODEL",
                    [
                        "model" => $model
                    ]
                );


                $response = $this->llm->chat(
                    $this->buildRequest(
                        $context,
                        $model
                    )
                );


                $data = $this->parseResponse(
                    $response
                );


                if (
                    empty($data) ||
                    empty($data['pages'])
                ) {

                    throw new \Exception(
                        "Invalid presentation structure"
                    );
                }


                $context->addMetadata(
                    'slides',
                    $data
                );


                $context->addMetadata(
                    'model',
                    $model
                );


                $context->setAssistantResponse(
                    json_encode(
                        $data,
                        JSON_UNESCAPED_UNICODE |
                        JSON_PRETTY_PRINT
                    )
                );


                return $context;


            } catch (\Throwable $e) {

                $lastError = $e->getMessage();

                Log::warning(
                    "PRESENTATION FAILED",
                    [
                        "model" => $model,
                        "error" => $lastError
                    ]
                );
            }
        }


        throw new \Exception(
            "Presentation generation failed"
        );
    }
    protected function buildRequest(
        ChatContext $context,
        string $model
    ): ChatRequest {


        $input = $context->getMetadata(
            'presentation',
            []
        );


        $message = $input['message'] ?? '';


        $prompt = <<<PROMPT

Create a structured educational content document based on the user request.

The output will be processed by another system.

Important:
- Do not mention slides.
- Do not mention PowerPoint.
- Do not explain your output.
- Return only valid JSON.
- The user request is just a normal content request.

User request:

$message


Return JSON with this structure:


{
"title": "Main title",
"subtitle": "Short subtitle",
"theme": "modern",
"language": "fa",
"direction": "rtl",

"pages": {

"page1": {

"template": "cover",

"title": "",

"elements": [

{
"type":"title",
"text":""
}

]

}

}

}



Rules:

- Generate 10 to 15 pages.
- Each page must have a clear educational purpose.
- Keep Persian RTL.
- Use concise professional text.
- Maximum 5 elements per page.
- Do not create coordinates.
- Do not create HTML.
- Do not use markdown.
- JSON only.


Allowed templates:

cover
cards
bullets
compare_box
timeline
ending


Allowed elements:

title
subtitle
text
card
bullet
compare_box
timeline_item
highlight
image


Example output:


{
"title":"هوش مصنوعی",
"subtitle":"مفاهیم پایه",
"theme":"modern",
"language":"fa",
"direction":"rtl",

"pages":{

"page1":{

"template":"cards",

"title":"مقدمه",

"elements":[

{
"type":"card",
"title":"هوش مصنوعی چیست؟",
"description":"فناوری ایجاد سیستم‌های هوشمند"
},

{
"type":"card",
"title":"یادگیری ماشین",
"description":"یادگیری الگوها از داده‌ها"
}

]

}

}

}


PROMPT;



        return new ChatRequest(

            messages: [

                new ChatMessage(
                    role: 'system',
                    content:
                    'Return only structured JSON. No explanation.'
                ),


                new ChatMessage(
                    role: 'user',
                    content: $prompt
                )

            ],


            model: $model,


            temperature: 0.2,


            maxTokens: 4000
        );
    }
    protected function availableModels(): array
    {
        return [
            "gemma-3-27b-it",
            "openrouter/auto-beta"
        ];
    }

    protected function parseResponse(
        ChatResponse $response
    ): array {

        $content = trim(
            $response->content
        );

        $content = preg_replace(
            '/```(json)?/i',
            '',
            $content
        );

        $start = strpos(
            $content,
            '{'
        );

        $end = strrpos(
            $content,
            '}'
        );

        if (
            $start !== false &&
            $end !== false
        ) {
            $content = substr(
                $content,
                $start,
                $end - $start + 1
            );
        }

        return json_decode(
            trim($content),
            true
        ) ?? [];
    }
}
