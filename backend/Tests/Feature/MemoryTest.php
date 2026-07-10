<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Application\Services\Memory\MemoryService;
use App\Domain\Memory\DTOs\MemoryDTO;

class MemoryTest extends TestCase
{
    private MemoryService $service;
    private string $testUserId = 'test-user-mem-789';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MemoryService::class);
    }

    public function test_can_create_a_memory()
    {
        $mem = $this->service->storeMemory(
            $this->testUserId,
            'User is a Laravel developer',
            8,
            'skill',
            'message:test123'
        );

        $this->assertInstanceOf(MemoryDTO::class, $mem);
        $this->assertNotEmpty($mem->id);
        $this->assertEquals($this->testUserId, $mem->user_id);
        $this->assertEquals('User is a Laravel developer', $mem->text);
        $this->assertEquals(8, $mem->importance);
        $this->assertEquals('skill', $mem->category);
        $this->assertEquals('message:test123', $mem->source_message);
    }

    public function test_can_find_a_memory_by_id()
    {
        $created = $this->service->storeMemory($this->testUserId, 'Test Memory', 6);
        $found = $this->service->getMemory($created->id);

        $this->assertNotNull($found);
        $this->assertEquals($created->id, $found->id);
        $this->assertEquals('Test Memory', $found->text);
        $this->assertEquals(6, $found->importance);
    }

    public function test_can_get_user_memories_with_min_importance()
    {
        $this->service->storeMemory($this->testUserId, 'Low importance', 3);
        $this->service->storeMemory($this->testUserId, 'High importance', 8);
        $this->service->storeMemory($this->testUserId, 'Medium importance', 6);

        $all = $this->service->getUserMemories($this->testUserId, 0);
        $this->assertCount(3, $all);

        $important = $this->service->getUserMemories($this->testUserId, 6);
        $this->assertCount(2, $important);
        foreach ($important as $mem) {
            $this->assertGreaterThanOrEqual(6, $mem->importance);
        }
    }

    public function test_can_get_memories_by_category()
    {
        $this->service->storeMemory($this->testUserId, 'Laravel expert', 8, 'skill');
        $this->service->storeMemory($this->testUserId, 'Loves dark mode', 6, 'preference');
        $this->service->storeMemory($this->testUserId, 'Uses VSCode', 7, 'tool');
        $this->service->storeMemory($this->testUserId, 'Another skill', 9, 'skill');

        $skills = $this->service->getMemoriesByCategory($this->testUserId, 'skill');
        $this->assertCount(2, $skills);

        $preferences = $this->service->getMemoriesByCategory($this->testUserId, 'preference');
        $this->assertCount(1, $preferences);
        $this->assertEquals('Loves dark mode', $preferences[0]->text);
    }

    public function test_can_update_a_memory()
    {
        $mem = $this->service->storeMemory($this->testUserId, 'Old text', 5, 'old-category');
        $updated = $this->service->updateMemory($mem->id, [
            'text' => 'New text',
            'importance' => 9,
            'category' => 'updated-category'
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals('New text', $updated->text);
        $this->assertEquals(9, $updated->importance);
        $this->assertEquals('updated-category', $updated->category);
    }

    public function test_can_touch_last_used()
    {
        $mem = $this->service->storeMemory($this->testUserId, 'Touch test', 5);
        $oldLastUsed = $mem->last_used;

        usleep(100000);
        $touched = $this->service->touchMemory($mem->id);
        $this->assertNotNull($touched);
        $this->assertNotEquals($oldLastUsed, $touched->last_used);
    }

    public function test_can_delete_a_memory()
    {
        $mem = $this->service->storeMemory($this->testUserId, 'To be deleted', 5);
        $result = $this->service->deleteMemory($mem->id);
        $this->assertTrue($result);

        $found = $this->service->getMemory($mem->id);
        $this->assertNull($found);
    }

    public function test_can_delete_all_user_memories()
    {
        $this->service->storeMemory($this->testUserId, 'Memory 1', 5);
        $this->service->storeMemory($this->testUserId, 'Memory 2', 6);
        $this->service->storeMemory($this->testUserId, 'Memory 3', 7);

        $result = $this->service->deleteAllUserMemories($this->testUserId);
        $this->assertTrue($result);

        $memories = $this->service->getUserMemories($this->testUserId);
        $this->assertCount(0, $memories);
    }

    public function test_respects_importance_filtering()
    {
        $this->service->storeMemory($this->testUserId, 'Low', 2);
        $this->service->storeMemory($this->testUserId, 'Medium', 5);
        $this->service->storeMemory($this->testUserId, 'High', 8);

        $filtered = $this->service->getUserMemories($this->testUserId, 6);
        $this->assertCount(1, $filtered);
        $this->assertEquals('High', $filtered[0]->text);

        $filtered = $this->service->getUserMemories($this->testUserId, 3);
        $this->assertCount(2, $filtered);
    }
}
