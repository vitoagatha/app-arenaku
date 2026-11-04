<?php

namespace Tests\Support;

use Illuminate\Foundation\Application;
use RuntimeException;

final class TestDatabase
{
    public static function assertSafe(Application $app): void
    {
        $connection = $app['config']->get('database.connections.mysql', []);
        if (!defined('ARENAKU_TEST_DATABASE') || !defined('ARENAKU_TEST_DATABASE_USER')
            || !$app->environment('testing')
            || $app['config']->get('database.default') !== 'mysql'
            || ($connection['database'] ?? null) !== ARENAKU_TEST_DATABASE
            || ($connection['username'] ?? null) !== ARENAKU_TEST_DATABASE_USER
            || !empty($connection['url'])
            || !preg_match('/^arenaku_stage2_test_[a-f0-9]{12}$/D', ARENAKU_TEST_DATABASE)
            || !in_array($connection['host'] ?? '', ['localhost', '127.0.0.1'], true)) {
            throw new RuntimeException('Unsafe test database: run php tests/run.php; never use the development database.');
        }
    }
}
