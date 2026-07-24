<?php

namespace App\Application\Services;


use App\Domain\LLM\ChatResponse;
use App\Models\AiModel;
use App\Models\TokenWallet;
use App\Models\Workspace;

class TokenUsageService
{

    public function __construct(

        protected TokenWalletService $walletService,

        protected CostCalculator $costCalculator

    ) {

    }
    public function consume(
        Workspace $workspace,
        AiModel $model,
        ChatResponse $response,
        ?string $conversationId = null
    ): void {


        $tokens =
            $response
                ->usage
                ->totalTokens;


        app(TokenWalletService::class)
            ->consume(
                $workspace,
                $tokens
            );
        $cost =
            $this->costCalculator
                ->calculate(

                    $model,

                    $response->usage->inputTokens,

                    $response->usage->outputTokens

                );


        app(UsageService::class)
            ->create(

                $workspace,

                [

                    'ai_model_id' => $model->id,


                    'provider' =>
                        $model->provider?->name,


                    'model' =>
                        $model->key,


                    'input_tokens' =>
                        $response->usage->inputTokens,


                    'output_tokens' =>
                        $response->usage->outputTokens,


                    'total_tokens' =>
                        $tokens,


                    'input_cost' =>
                        $cost['input_cost'],


                    'output_cost' =>
                        $cost['output_cost'],


                    'total_cost' =>
                        $cost['total_cost'],


                    'status' => 'success',


                    'metadata' => [

                        'conversation_id' => $conversationId

                    ]

                ]

            );

    }

}
