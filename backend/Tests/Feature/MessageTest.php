<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Application\Services\Conversation\ConversationService;
use App\Application\Services\Message\MessageService;
use App\Domain\Message\DTOs\MessageDTO;

class MessageTest extends TestCase
{
    private MessageService $messageService;
    private ConversationService $conversationService;
    private string $testUserId = 'test-user-msg-456';
    private string $conversationId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->messageService = app(MessageService::class);
        $this->conversationService = app(ConversationService::class);

        $conv = $this->conversationService->startNewConversation($this->testUserId, 'Message Test Conversation');
        $this->conversationId = $conv->id;
    }

    public function test_can_create_a_message()
    {
        $msg = $this->messageService->addMessage(
            $this->conversationId,
            'user',
            'Hello, this is a test message.',
            10,
            ['key' => 'value']
        );

        $this->assertInstanceOf(MessageDTO::class, $msg);
        $this->assertNotEmpty($msg->id);
        $this->assertEquals($this->conversationId, $msg->conversation);
        $this->assertEquals('user', $msg->role);
        $this->assertEquals('Hello, this is a test message.', $msg->content);
        $this->assertEquals(10, $msg->token_count);
        $this->assertEquals(['key' => 'value'], $msg->metadata);
    }

    public function test_can_find_a_message_by_id()
    {
        $created = $this->messageService->addMessage($this->conversationId, 'assistant', 'Response message');
        $found = $this->messageService->getMessage($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals('assistant', $found->role);
        $this->assertEquals('Response message', $found->content);
    }

    public function test_can_get_messages_of_a_conversation()
    {
        $this->messageService->addMessage($this->conversationId, 'user', 'First message');
        $this->messageService->addMessage($this->conversationId, 'assistant', 'Second message');
        $this->messageService->addMessage($this->conversationId, 'user', 'Third message');

        $messages = $this->messageService->getConversationMessages($this->conversationId);
        $this->assertCount(3, $messages);
        $this->assertContainsOnlyInstancesOf(MessageDTO::class, $messages);
    }

    public function test_can_get_last_messages()
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->messageService->addMessage($this->conversationId, 'user', "Message $i");
        }

        $last = $this->messageService->getLastMessages($this->conversationId, 3);
        $this->assertCount(3, $last);
        $this->assertEquals('Message 5', $last[0]->content);
        $this->assertEquals('Message 4', $last[1]->content);
        $this->assertEquals('Message 3', $last[2]->content);
    }

    public function test_can_update_a_message()
    {
        $msg = $this->messageService->addMessage($this->conversationId, 'user', 'Old content', 5);
        $updated = $this->messageService->updateMessage($msg->id, [
            'content' => 'New content',
            'token_count' => 20
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals('New content', $updated->content);
        $this->assertEquals(20, $updated->token_count);
    }

    public function test_can_delete_a_message()
    {
        $msg = $this->messageService->addMessage($this->conversationId, 'user', 'To be deleted');
        $result = $this->messageService->deleteMessage($msg->id);
        $this->assertTrue($result);

        $found = $this->messageService->getMessage($msg->id);
        $this->assertNull($found);
    }

    public function test_can_delete_all_messages_of_a_conversation()
    {
        $this->messageService->addMessage($this->conversationId, 'user', 'Message 1');
        $this->messageService->addMessage($this->conversationId, 'user', 'Message 2');

        $result = $this->messageService->deleteConversationMessages($this->conversationId);
        $this->assertTrue($result);

        $messages = $this->messageService->getConversationMessages($this->conversationId);
        $this->assertCount(0, $messages);
    }

    public function test_can_create_messages_with_different_roles()
    {
        $userMsg = $this->messageService->addMessage($this->conversationId, 'user', 'User message');
        $assistantMsg = $this->messageService->addMessage($this->conversationId, 'assistant', 'Assistant message');
        $systemMsg = $this->messageService->addMessage($this->conversationId, 'system', 'System message');

        $this->assertEquals('user', $userMsg->role);
        $this->assertEquals('assistant', $assistantMsg->role);
        $this->assertEquals('system', $systemMsg->role);
    }
}
