<?
namespace App\Domain\Contracts;
interface PromptBuilder
{
    public function build(ChatContext $context): array;
}
