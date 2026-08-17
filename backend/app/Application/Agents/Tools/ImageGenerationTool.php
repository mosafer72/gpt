<?php

namespace App\Application\Agents\Tools;

use App\Application\Agents\Contracts\AgentTool;
use App\Application\Context\ChatContext;
use App\Models\User;

class ImageGenerationTool implements AgentTool
{
    public function __construct(
        protected ImageGenerationService $service
    ) {
    }

    public function name(): string
    {
        return 'generate_image';
    }

    public function description(): string
    {
        return 'Generate an image when the user explicitly asks to create, draw, render, or generate an image.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'prompt' => [
                    'type' => 'string',
                    'description' => 'Detailed image generation prompt.',
                ],
            ],
            'required' => [
                'prompt',
            ],
        ];
    }

    public function execute(
        ChatContext $context,
        User $user,
        array $arguments = []
    ): mixed {
        $prompt = trim(
            (string) ($arguments['prompt'] ?? '')
        );

        if ($prompt === '') {
            throw new \InvalidArgumentException(
                'generate_image requires a prompt.'
            );
        }

        $context->addMetadata(
            'image_prompt',
            $prompt
        );

        return $this->service->generate(
            $context,
            $prompt
        );
    }
}
