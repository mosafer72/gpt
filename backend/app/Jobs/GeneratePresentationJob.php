<?php

namespace App\Jobs;

use App\Models\Document;
use App\Application\Context\ChatContext;
use App\Application\Pipelines\Presentation\PresentationWorkspacePipeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class GeneratePresentationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document
    ) {
    }


    public function handle(
        PresentationWorkspacePipeline $pipeline
    ): void {

        $metadata = $this->document->metadata;

        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }


        if (
            in_array(
                $metadata['generation_state'] ?? null,
                ['running', 'completed']
            )
        ) {
            return;
        }


        try {

            $metadata['generation_state'] = 'running';

            $this->document->update([
                'status' => 'processing',
                'metadata' => $metadata
            ]);


            $message = $metadata['message'] ?? '';

            if (!$message) {
                throw new \Exception(
                    "Presentation message empty"
                );
            }


            $context = new ChatContext(
                userId: (string) $this->document->user_id,
                conversationId: null,
                currentMessage: $message
            );


            $context->addMetadata(
                'presentation',
                $metadata
            );


            $pipeline->handle($context);


            $content = $context->getMetadata(
                'slides'
            );


            if (
                !$content ||
                empty($content['pages'])
            ) {
                throw new \Exception(
                    "No pages generated"
                );
            }


            $jsonData = [
                "theme" => $content['theme'] ?? "modern",
                "language" => "fa",
                "direction" => "rtl",
                "title" => $content['title'] ?? "",
                "subtitle" => $content['subtitle'] ?? "",
                "pages" => array_values(
                    $content['pages']
                )
            ];


            $response = Http::timeout(300)
                ->post(
                    "http://127.0.0.1:3001/generate",
                    $jsonData
                );


            if (!$response->successful()) {
                throw new \Exception(
                    "PPT service error: " . $response->body()
                );
            }


            $result = $response->json();


            $file = $result['file'] ?? null;

            if (!$file) {
                throw new \Exception(
                    "PPT file missing"
                );
            }


            $source = base_path(
                "presentation-service/output/" . $file
            );


            $relative =
                "presentations/" . $this->document->id . ".pptx";


            $destination = storage_path(
                "app/public/" . $relative
            );


            if (!is_dir(dirname($destination))) {
                mkdir(
                    dirname($destination),
                    0777,
                    true
                );
            }


            copy(
                $source,
                $destination
            );


            $this->document->update([

                "path" => $relative,

                "size" => filesize($destination),

                "status" => "completed",

                "metadata" => array_merge(
                    $metadata,
                    [
                        "generation_state" => "completed",
                        "pages_count" => count($jsonData['pages'])
                    ]
                )

            ]);


        } catch (\Throwable $e) {


            $this->document->update([

                "status" => "failed",

                "error" => $e->getMessage(),

                "metadata" => array_merge(
                    $metadata,
                    [
                        "generation_state" => "failed"
                    ]
                )

            ]);

        }

    }
}
