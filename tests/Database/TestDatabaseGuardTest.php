<?php

namespace Tests\Database;

use RuntimeException;
use Tests\Support\TestDatabase;
use Tests\TestCase;

class TestDatabaseGuardTest extends TestCase
{
    public function test_guard_rejects_wrong_database_account_host_url_and_driver_before_queries(): void
    {
        $base = config('database');
        $cases = [
            ['database.connections.mysql.database', 'app_arenaku'],
            ['database.connections.mysql.username', 'root'],
            ['database.connections.mysql.host', 'remote.example.test'],
            ['database.connections.mysql.url', 'mysql://untrusted'],
            ['database.default', 'sqlite'],
        ];
        foreach ($cases as [$key, $value]) {
            config(['database' => $base, $key => $value]);
            try {
                TestDatabase::assertSafe($this->app);
                $this->fail('Unsafe test configuration accepted: '.$key);
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('Unsafe test database', $e->getMessage());
            } finally {
                config(['database' => $base]);
            }
        }
        TestDatabase::assertSafe($this->app);
        $this->assertTrue(true);
    }
}
