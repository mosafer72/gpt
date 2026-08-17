<?php

namespace App\Application\Agents;

final class AgentDecision
{
    public const ANSWER = '<answer/>';
    public const MORE_HISTORY = '<more_history/>';
    public const WEB_SEARCH = '<web_search/>';
    public const IMAGE = '<image/>';

    /**
     * Normalize and validate agent decision.
     */
    public static function normalize(
        string $decision
    ): string {
        $decision = trim($decision);

        /*
         * Remove markdown code fences.
         */
        $decision = preg_replace(
            '/^```(?:text|json)?\s*/i',
            '',
            $decision
        );

        $decision = preg_replace(
            '/\s*```$/',
            '',
            $decision
        );

        $decision = trim($decision);

        /*
         * Exact modern decisions.
         */
        return match ($decision) {

            self::ANSWER =>
                self::ANSWER,

            self::MORE_HISTORY =>
                self::MORE_HISTORY,

            self::WEB_SEARCH =>
                self::WEB_SEARCH,

            self::IMAGE =>
                self::IMAGE,

            /*
             * Backward compatibility.
             */
            '<hc/>' =>
                self::ANSWER,

            '<mo/>' =>
                self::MORE_HISTORY,

            '<ws/>' =>
                self::WEB_SEARCH,

            default =>
                self::extractFromContent($decision),
        };
    }

    /**
     * Extract a valid decision if the model
     * returned additional text.
     */
    protected static function extractFromContent(
        string $content
    ): string {
        $decisions = [
            self::ANSWER,
            self::MORE_HISTORY,
            self::WEB_SEARCH,
            self::IMAGE,

            /*
             * Legacy routes.
             */
            '<hc/>',
            '<mo/>',
            '<ws/>',
        ];

        foreach ($decisions as $decision) {

            if (str_contains($content, $decision)) {

                return match ($decision) {

                    self::ANSWER =>
                        self::ANSWER,

                    self::MORE_HISTORY =>
                        self::MORE_HISTORY,

                    self::WEB_SEARCH =>
                        self::WEB_SEARCH,

                    self::IMAGE =>
                        self::IMAGE,

                    '<hc/>' =>
                        self::ANSWER,

                    '<mo/>' =>
                        self::MORE_HISTORY,

                    '<ws/>' =>
                        self::WEB_SEARCH,
                };
            }
        }

        throw new \RuntimeException(
            'Invalid agent decision: ' . $content
        );
    }
}
