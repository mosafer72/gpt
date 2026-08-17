<?php

namespace App\Application\Agents;

use App\Application\Context\ChatContext;
use App\Domain\LLM\ChatRequest;
use App\Domain\LLM\ChatResponse;
use App\Models\User;

class AgentRuntime
{
    /**
     * Maximum number of agent iterations.
     *
     * Prevents accidental infinite loops.
     */
    protected int $maxIterations = 12;

    public function run(
        ChatContext $context,
        User $user,
        callable $decide,
        callable $execute,
        callable $answer,
    ): mixed {
        $iteration = 0;

        while ($iteration < $this->maxIterations) {
            $iteration++;

            $context->addMetadata(
                'agent_iteration',
                $iteration
            );

            /*
             * ==========================================
             * 1. ASK MAIN MODEL
             * ==========================================
             *
             * The main model is the agent brain.
             */
            $decision = trim(
                (string) $decide(
                    $context,
                    $user
                )
            );

            $context->addMetadata(
                'agent_last_decision',
                $decision
            );

            /*
             * ==========================================
             * 2. EXECUTE DECISION
             * ==========================================
             */

            $result = $execute(
                $decision,
                $context,
                $user
            );

            /*
             * ==========================================
             * 3. FINISH
             * ==========================================
             */

            if (
                is_array($result)
                && ($result['finish'] ?? false)
            ) {
                return $result['response'] ?? null;
            }

            /*
             * ==========================================
             * 4. CONTINUE
             * ==========================================
             *
             * Tool/history result has already been
             * added to context.
             *
             * Main model will now decide again.
             */
        }

        /*
         * Safety fallback.
         */
        return $answer(
            $context,
            $user
        );
    }

    public function maxIterations(): int
    {
        return $this->maxIterations;
    }

    public function setMaxIterations(int $maxIterations): self
    {
        $this->maxIterations = max(
            1,
            $maxIterations
        );

        return $this;
    }
}
