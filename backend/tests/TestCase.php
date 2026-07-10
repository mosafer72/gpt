<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use SurrealDB\Laravel\Facades\SurrealDB;
use SurrealDB\SDK\Query\BoundQuery;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanDatabase();
    }

    protected function cleanDatabase(): void
    {
        try {
            SurrealDB::query(new BoundQuery("REMOVE TABLE conversation;"));
        } catch (\Exception $e) {
            // table doesn't exist, ignore
        }
        try {
            SurrealDB::query(new BoundQuery("REMOVE TABLE message;"));
        } catch (\Exception $e) {
            // ignore
        }
        try {
            SurrealDB::query(new BoundQuery("REMOVE TABLE memory;"));
        } catch (\Exception $e) {
            // ignore
        }
    }

    protected function tearDown(): void
    {
        $this->cleanDatabase();
        parent::tearDown();
    }
}
