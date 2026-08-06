<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'zarinpal' => [
        'merchant_id' => env('ZARINPAL_MERCHANT_ID'),
        'sandbox' => env('ZARINPAL_SANDBOX', true),
    ],

    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => env('SMSIR_TEMPLATE_ID'),
    ],
    'openai' => [

        'key' => env('OPENAI_API_KEY'),
    ],
    'gapgpt' => [

        'key' => env('GAPGPT_API_KEY'),

        'base_url' => env(
            'GAPGPT_BASE_URL',
            'https://api.gapgpt.app/v1'
        ),

        'model' => env(
            'GAPGPT_MODEL'
        ),

    ],

    'planner' => [

        'url' => env(
            'PLANNER_URL',
            'http://127.0.0.1:11434'
        ),

        'model' => env(
            'PLANNER_MODEL',
            'qwen2.5-64k:latest'
        ),

    ],
    'local_ai' => [

        'url' => 'http://localhost:11434/v1',

        'embedding_model' => 'bge-m3:latest',

    ],

    'embedding' => [

        'model' => env(
            'EMBEDDING_MODEL',
            'bge-m3'
        ),

    ],
    'cloudflare' => [

        'account_id' => env(
            'CF_ACCOUNT_ID'
        ),

        'api_token' => env(
            'CF_API_TOKEN'
        ),

        'base_url' => env(
            'CF_BASE_URL',
            'https://api.cloudflare.com/client/v4/accounts'
        ),


        // Worker Image Provider
        'image_worker_url' => env(
            'CLOUDFLARE_IMAGE_WORKER_URL'
        ),

        'image_worker_key' => env(
            'CLOUDFLARE_IMAGE_WORKER_KEY'
        ),


        // Direct AI API (future)
        'ai_url' => env(
            'CLOUDFLARE_AI_URL'
        ),

        'ai_key' => env(
            'CLOUDFLARE_AI_KEY'
        ),


        'models' => [

            'chat' => env(
                'CF_CHAT_MODEL',
                '@cf/qwen/qwen3-30b-a3b-fp8'
            ),

            'image' => env(
                'CF_IMAGE_MODEL',
                '@cf/openai/gpt-image-2'
            ),

            'embedding' => env(
                'CF_EMBEDDING_MODEL',
                '@cf/qwen/qwen3-embedding-0.6b'
            ),

        ],

    ],
];



