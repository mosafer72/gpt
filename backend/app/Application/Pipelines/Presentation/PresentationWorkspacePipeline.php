<?php

namespace App\Application\Pipelines\Presentation;

use App\Application\Context\ChatContext;
use App\Application\Services\WorkspaceAccessService;
use App\Domain\Contracts\WorkspacePipeline;
use App\Domain\LLM\ChatMessage;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Infrastructure\LLM\Cloudflare\CloudflareProvider;
use App\Infrastructure\LLM\GapGPTProvider;
use App\Models\AiModel;
use Illuminate\Support\Facades\Log;

class PresentationWorkspacePipeline implements WorkspacePipeline
{
    public function __construct(
        protected CloudflareProvider $CloudflareProvider,
        protected WorkspaceAccessService $workspaceAccessService,
    ) {

    }

    public function stream(
        ChatContext $context,
        callable $send
    ): void {
    }


    public function handle(ChatContext $context): ChatContext
    {
        \Log::info(
            "PRESENTATION CONTEXT",
            $context->toArray()
        );


        $model = $this->workspaceAccessService->resolveModel(
            $context->workspace,
            $context->model
        );


        $provider = \App\Models\LlmProvider::where(
            "id",
            $model->provider_id
        )->first();


        try {


            \Log::info(
                "TRY PRESENTATION MODEL",
                [
                    "model_id" => $model->id,
                    "model_key" => $model->key,
                    "provider" => $provider->name
                ]
            );



            $response = $this->{$provider->name}->chat(
                $this->buildRequest(
                    $context,
                    $model
                )
            );



            \Log::info(
                "PRESENTATION RAW RESPONSE",
                [
                    "preview" =>
                            $response->content,
                ]
            );



            $content = trim(
                $response->content
            );



            /*
            |--------------------------------------------------------------------------
            | Remove Markdown fences
            |--------------------------------------------------------------------------
            */

            if (
                str_contains(
                    $content,
                    '```'
                )
            ) {

                $content = preg_replace(
                    '/```(xml|html)?/i',
                    '',
                    $content
                );


                $content = trim(
                    $content
                );

            }



            /*
            |--------------------------------------------------------------------------
            | Extract scientific document root
            |--------------------------------------------------------------------------
            */

            $content = $this->extractPresentationRoot(
                $content
            );



            /*
            |--------------------------------------------------------------------------
            | Validate Presentation Structure
            |--------------------------------------------------------------------------
            */

            $valid =
                str_contains(
                    $content,
                    '<scientific-document>'
                )
                ||
                str_contains(
                    $content,
                    '<div class="scientific-document">'
                );


            if (
                empty($content)
                ||
                !$valid
            ) {


                \Log::error(
                    "INVALID PRESENTATION STRUCTURE",
                    [
                        "preview" =>
                            mb_substr(
                                $content,
                                0,
                                2000
                            )
                    ]
                );


                throw new \Exception(
                    "Invalid presentation structure"
                );

            }



            /*
            |--------------------------------------------------------------------------
            | Save Result
            |--------------------------------------------------------------------------
            */

            $context->addMetadata(
                'presentation_html',
                $content
            );


            $context->addMetadata(
                'model',
                [
                    'id' => $model->id,
                    'key' => $model->key
                ]
            );



            $context->setAssistantResponse(
                $content
            );



            \Log::info(
                "PRESENTATION SUCCESS",
                [
                    "length" =>
                        strlen($content)
                ]
            );



            return $context;



        } catch (\Throwable $e) {


            \Log::warning(
                "PRESENTATION FAILED",
                [
                    "model" =>
                        $model->key,

                    "error" =>
                        $e->getMessage(),

                    "line" =>
                        $e->getLine(),

                    "file" =>
                        $e->getFile()
                ]
            );



            $context->addMetadata(
                'presentation_error',
                $e->getMessage()
            );


            return $context;

        }
    }
protected function buildRequest(
        ChatContext $context,
        string $model
    ): ChatRequest {

        $input =$context->getMetadata(
            'presentation',
            []
        );

        $message = trim($input['message'] ?? '');

        $prompt = <<<PROMPT
Create a comprehensive, highly detailed scientific educational document based on this topic:

Topic:
$message


IMPORTANT CRITICAL RULES:
- The output is parsed by a strict, custom XML parser.
- Generate ONLY the final XML document.
- NO explanations, NO markdown block wrappers (NO ```xml), NO text before or after the XML.


==================================================
OUTPUT FORMAT
==================================================

The output MUST start with:
<scientific-document>

The output MUST end with:
</scientific-document>


==================================================
XML ROOT STRUCTURE
==================================================

The only allowed root tag is:
<scientific-document>

Direct children are ONLY:
<page>


==================================================
PAGE STRUCTURE
==================================================

Generate exactly 10 pages in logical sequence.

Every page MUST follow this structure:

<page>
    <page-header>
        <h1>Page Title in Persian</h1>
    </page-header>

    COMPONENTS
</page>


Rules:
- page-header is mandatory and contains ONLY <h1>.
- h1 contains plain text in Persian (RTL friendly).
- Each page must contain minimum 3 and maximum 4 rich components (excluding page-header).
- Provide detailed, deep, and practical scientific text for each component.


==================================================
PAGE CONTENT PLAN (10 PAGES)
==================================================

Page 1: Title & Presentation Introduction (عنوان اصلی پژوهش، زیرعنوان تخصصی و معرفی اجمالی موضوع)
Page 2: Introduction & Domain Context (مقدمه تخصصی و ضرورت)
Page 3: Problem Statement & Motivation (تبيین دقیق مسئله و چالش‌های موجود)
Page 4: Theoretical Background & Foundations (پیش‌زمینه نظری و مفاهیم پایه)
Page 5: Main Innovation & Core Concept (نوآوری اصلی، ایده مرکزی و معماری)
Page 6: Technical Methodology & Workflow (روش‌شناسی، الگوریتم و فرآیند اجرا)
Page 7: System Architecture & Technical Details (جزئیات فنی، فرمول‌ها یا کدها)
Page 8: Practical Application & Mechanism (مکانیسم‌های عملی و نحوه کارکرد)
Page 9: Comparative Analysis & Evaluation (مقایسه عمیق روش با سایر روش‌ها و ارزیابی)
Page 10: Conclusion & Presentation Ending (نتیجه‌گیری کلی، جمع‌بندی و پایان)


==================================================
PAGE 1 (TITLE & INTRODUCTION) MANDATORY CONTENT
==================================================
Page 1 MUST include:
1. <page-header>
        <h1>عنوان اصلی پژوهش یا پروژه</h1>
   </page-header>
2. <concept-card>
        <h3>زیرعنوان تخصصی یا حوزه پژوهش</h3>
        <p>توضیح کوتاه و فشرده‌ای درباره ماهیت پژوهش و اهداف کلان آن.</p>
   </concept-card>
3. <scientific-text>
        شرح و مقدمه کوتاهی در خصوص اهمیت موضوع، ضرورت انجام پژوهش و زمینه‌های کاربرد آن که در اسلاید اول نمایش داده می‌شود.
   </scientific-text>


==================================================
ALLOWED COMPONENT TAGS & STRICT RULES
==================================================

ONLY these exact tags are allowed. Never invent new tags or rename existing ones.


1. Scientific Text:
<scientific-text>
Provide exhaustive, detailed Persian academic text explaining the scientific concept in depth.
</scientific-text>


2. Definition Box:
<definition-box>
Clear and precise academic definition of a key term.
</definition-box>


3. Concept Card:
<concept-card>
<h3>Concept Title in Persian</h3>
<p>Detailed explanation of the concept, its role, and practical importance.</p>
</concept-card>


4. Scientific Insight:
<insight-box>
Key practical or theoretical takeaway / insight.
</insight-box>


5. Scientific List:
<scientific-list>
<ul>
<li>At least 3 to 4 detailed items in Persian</li>
<li>Item two</li>
<li>Item three</li>
</ul>
</scientific-list>


6. Process:
<process>
<process-step>Phase 1: Detailed description of step</process-step>
<process-step>Phase 2: Detailed description of step</process-step>
<process-step>Phase 3: Detailed description of step</process-step>
</process>


7. Pipeline:
<pipeline>
<pipeline-node>Input / Stage 1</pipeline-node>
<pipeline-node>Processing / Stage 2</pipeline-node>
<pipeline-node>Output / Stage 3</pipeline-node>
</pipeline>


8. Comparison:
CRITICAL: Must contain <h1> title and <ul>/<li> list inside comparison-left and comparison-right!
<comparison>
    <comparison-left>
        <h1>روش / رویکرد موجود</h1>
        <ul>
            <li>معیار یا ویژگی اول</li>
            <li>معیار یا ویژگی دوم</li>
            <li>معیار یا ویژگی سوم</li>
        </ul>
    </comparison-left>
    <comparison-right>
        <h1>روش / رویکرد پیشنهادی</h1>
        <ul>
            <li>معیار یا ویژگی اول</li>
            <li>معیار یا ویژگی دوم</li>
            <li>معیار یا ویژگی سوم</li>
        </ul>
    </comparison-right>
</comparison>


9. Formula:
<formula>
Mathematical equation or formula text (e.g., L_total = L_cls + lambda * L_reg)
</formula>


10. Formula Explanation:
<formula-explanation>
Persian detailed explanation of terms and mathematical symbols used in the formula.
</formula-explanation>


11. Algorithm:
<algorithm>
<algorithm-step>Step 1: Algorithm initialization</algorithm-step>
<algorithm-step>Step 2: Core processing loop</algorithm-step>
<algorithm-step>Step 3: Output generation</algorithm-step>
</algorithm>


12. Code Block:
<code-block>
Raw code without markdown or HTML entities
</code-block>


13. Code Analysis:
<code-analysis>
Persian detailed line-by-line or architectural explanation of the code snippet.
</code-analysis>


14. Scientific Image:
Attribute requirement: ONLY type attribute is allowed with values: "scientific_diagram", "architecture", "flowchart", "conceptual_visualization".
<scientific-image type="architecture">
Comprehensive description of what the visual diagram represents.
</scientific-image>


==================================================
PAGE 10 (CONCLUSION & ENDING) MANDATORY CONTENT
==================================================

Page 10 MUST include:
1. An <insight-box> summarizing the final conclusion and takeaways.
2. A <scientific-text> clearly stating the ending message (e.g., "پایان ارائه - با سپاس از توجه شما").


==================================================
GLOBAL XML RULES
==================================================

- Write in formal, rich academic Persian. Technical English terms are allowed in parentheses.
- Do NOT output empty elements.
- Always close every opened tag.
- NO html attributes except type on <scientific-image>.
- Return ONLY pure valid XML without any markdown backticks.
PROMPT;

        return new ChatRequest(
            messages: [
                new ChatMessage(
                    role: 'system',
                    content: <<<SYSTEM
- <scientific-document ONLY XML You Your a are by custom directly document generator. is output parsed parser. production-grade scientific starting strict, valid with> and ending with </scientific-document>.
- Absolutely NO markdown wrapping (no ```xml or ```).
- Never add text outside root.
- Ensure high academic richness, complete Persian descriptions, and strict adherence to component schemas.
SYSTEM
                ),
                new ChatMessage(
                    role: 'user',
                    content: $prompt
                )
            ],
            model: $model,
            temperature: 0.1,
            maxTokens: 20000
        );
    }
        protected function parseResponse(
        ChatResponse $response
    ): string {

        $content = trim(
            $response->content
        );


        /*
        |--------------------------------------------------------------------------
        | Remove Markdown fences
        |--------------------------------------------------------------------------
        */

        if (
            str_contains(
                $content,
                '```'
            )
        ) {

            $content = preg_replace(
                '/```(xml)?/i',
                '',
                $content
            );

            $content = trim(
                $content
            );
        }



        /*
        |--------------------------------------------------------------------------
        | Extract scientific-document XML
        |--------------------------------------------------------------------------
        */

        $start = strpos(
            $content,
            '<scientific-document>'
        );


        $end = strrpos(
            $content,
            '</scientific-document>'
        );


        if (
            $start !== false &&
            $end !== false
        ) {

            $content = substr(
                $content,
                $start,
                ($end - $start)
                + strlen('</scientific-document>')
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Validate XML root
        |--------------------------------------------------------------------------
        */

        if (
            empty($content) ||
            !str_contains(
                $content,
                '<scientific-document>'
            ) ||
            !str_contains(
                $content,
                '</scientific-document>'
            )
        ) {


            Log::error(
                "PRESENTATION XML PARSE FAILED",
                [
                    "preview" =>
                        mb_substr(
                            $content,
                            0,
                            2000
                        )
                ]
            );


            throw new \Exception(
                "Invalid scientific XML structure"
            );

        }



        /*
        |--------------------------------------------------------------------------
        | Basic XML validation
        |--------------------------------------------------------------------------
        */

        libxml_use_internal_errors(true);


        $xml = simplexml_load_string(
            $content
        );


        if (
            $xml === false
        ) {

            $errors = [];

            foreach (
                libxml_get_errors()
                as $error
            ) {

                $errors[] =
                    trim(
                        $error->message
                    );

            }


            Log::error(
                "INVALID XML DOCUMENT",
                [
                    "errors" => $errors,
                    "preview" =>
                        mb_substr(
                            $content,
                            0,
                            2000
                        )
                ]
            );


            throw new \Exception(
                "Generated XML is not valid"
            );

        }



        return $content;
    }
    protected function extractPresentationRoot(
        string $content
    ): string {

        $roots = [

            [
                '<scientific-document>',
                '</scientific-document>'
            ],

            [
                '<div class="scientific-document">',
                '</div>'
            ]

        ];


        foreach ($roots as $root) {


            $start = strpos(
                $content,
                $root[0]
            );


            $end = strrpos(
                $content,
                $root[1]
            );


            if (
                $start !== false &&
                $end !== false
            ) {

                return substr(
                    $content,
                    $start,
                    ($end - $start)
                    + strlen($root[1])
                );

            }

        }


        return $content;
    }
}
