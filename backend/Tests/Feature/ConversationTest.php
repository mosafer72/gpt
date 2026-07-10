<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Application\Services\Conversation\ConversationService;
use App\Domain\Conversation\DTOs\ConversationDTO;

class ConversationTest extends TestCase
{
    private ConversationService $service;
    private string $testUserId = 'test-user-conv-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ConversationService::class);
    }

    public function test_can_create_a_conversation()
    {
        $conv = $this->service->startNewConversation($this->testUserId, 'My First Conversation');

        $this->assertInstanceOf(ConversationDTO::class, $conv);
        $this->assertNotEmpty($conv->id);
        $this->assertEquals('My First Conversation', $conv->title);
        $this->assertEquals('active', $conv->status);
        $this->assertEquals($this->testUserId, $conv->userId);
        $this->assertEquals('gpt-4o', $conv->model);
    }

    public function test_can_find_a_conversation_by_id()
    {
        $created = $this->service->startNewConversation($this->testUserId, 'Find Me');
        $found = $this->service->getConversation($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals('Find Me', $found->title);
    }

    public function test_returns_null_for_non_existent_conversation()
    {
        $found = $this->service->getConversation('conversation:non-existent-id');
        $this->assertNull($found);
    }

    public function test_can_update_a_conversation()
    {
        $conv = $this->service->startNewConversation($this->testUserId, 'Old Title');
        $updated = $this->service->updateConversation($conv->id, ['title' => 'Brand New Title']);

        $this->assertNotNull($updated);
        $this->assertEquals('Brand New Title', $updated->title);
        $this->assertEquals($conv->id, $updated->id);
    }

    public function test_can_soft_delete_and_restore_a_conversation()
    {
        $conv = $this->service->startNewConversation($this->testUserId, 'Soft Delete Test');

        $deleted = $this->service->deleteConversation($conv->id, true);
        $this->assertNotNull($deleted);
        $this->assertEquals('deleted', $deleted->status);

        $restored = $this->service->restoreConversation($conv->id);
        $this->assertNotNull($restored);
        $this->assertEquals('active', $restored->status);
    }

    public function test_can_archive_a_conversation()
    {
        $conv = $this->service->startNewConversation($this->testUserId, 'Archive Test');
        $archived = $this->service->archiveConversation($conv->id);

        $this->assertNotNull($archived);
        $this->assertEquals('archived', $archived->status);
    }

    public function test_can_hard_delete_a_conversation()
    {
        $conv = $this->service->startNewConversation($this->testUserId, 'Hard Delete Test');
        $result = $this->service->deleteConversation($conv->id, false);
        $this->assertTrue($result);

        $found = $this->service->getConversation($conv->id);
        $this->assertNull($found);
    }

    public function test_can_get_all_user_conversations()
    {
        $this->service->startNewConversation($this->testUserId, 'Conv 1');
        $this->service->startNewConversation($this->testUserId, 'Conv 2');
        $this->service->startNewConversation($this->testUserId, 'Conv 3');

        $list = $this->service->getUserConversations($this->testUserId);
        $this->assertCount(3, $list);
        $this->assertContainsOnlyInstancesOf(ConversationDTO::class, $list);
    }

    public function test_can_soft_delete_all_user_conversations()
    {
        $this->service->startNewConversation($this->testUserId, 'Batch 1');
        $this->service->startNewConversation($this->testUserId, 'Batch 2');

        $deletedList = $this->service->deleteAllUserConversations($this->testUserId, true);
        $this->assertCount(2, $deletedList);
        foreach ($deletedList as $dto) {
            $this->assertEquals('deleted', $dto->status);
        }

        $active = $this->service->getUserConversations($this->testUserId);
        $this->assertCount(0, $active);
    }

    public function test_can_archive_all_user_conversations()
    {
        $this->service->startNewConversation($this->testUserId, 'Archive 1');
        $this->service->startNewConversation($this->testUserId, 'Archive 2');

        $archivedList = $this->service->archiveAllUserConversations($this->testUserId);
        $this->assertCount(2, $archivedList);
        foreach ($archivedList as $dto) {
            $this->assertEquals('archived', $dto->status);
        }
    }
}
