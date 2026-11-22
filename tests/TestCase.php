<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $dbName = config('database.connections.mysql.database');
        if ($dbName === 'eatalyft_local') {
            $this->fail('Tests must not run against eatalyft_local database. Current: ' . $dbName);
        }
    }
}
