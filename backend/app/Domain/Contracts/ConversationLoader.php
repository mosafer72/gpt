<?
namespace App\Domain\Contracts;
interface ConversationLoader
{
    public function load(
        string $conversationId
    ): array;
}
