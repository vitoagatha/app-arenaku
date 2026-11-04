<?php

// A dedicated, temporary database and restricted credentials are mandatory for every run.
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$isolated = null;
$exit = 1;
try {
    $isolated = new Tests\Support\IsolatedMysql();
    $isolated->provision();
    $GLOBALS['arenaku_audit_snapshot'] = $isolated->snapshot;
    require 'tests/bootstrap.php';

    $app = require 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Tests\Support\TestDatabase::assertSafe($app);
    $migrationExit = $app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate', ['--force' => true]);
    if ($migrationExit !== 0 || !$app['db']->getSchemaBuilder()->hasTable('konfirmasi_bookings')) {
        throw new RuntimeException('Isolated baseline migration failed.');
    }

    $exit = (new PHPUnit\TextUI\Application())->run([
        'phpunit', '--no-configuration', '--do-not-cache-result', '--colors=never',
        'tests/Unit', 'tests/Feature',
        'tests/Database/ManagementAuthorizationTest.php',
        'tests/Database/TestDatabaseGuardTest.php',
        'tests/Database/HourlySchemaRehearsalTest.php',
    ]);
    if (!$isolated->sourceUnchanged()) {
        fwrite(STDERR, "Source operational snapshot changed during the test run; investigate concurrent changes.\n");
        $exit = 1;
    } else {
        echo "PASS: source operational snapshot is unchanged.\n";
    }
} catch (Throwable $e) {
    // Do not print database exception messages: they may contain credentials or SQL values.
    fwrite(STDERR, 'Test setup/run failed: '.get_class($e).' (code '.$e->getCode().").\n");
    if ($e instanceof RuntimeException && !$e instanceof PDOException) {
        fwrite(STDERR, $e->getMessage()."\n");
    }
    $exit = 1;
} finally {
    if ($isolated) {
        try {
            $isolated->cleanup();
            echo "Temporary test schema and accounts removed.\n";
        } catch (Throwable $e) {
            fwrite(STDERR, "Temporary database cleanup failed; inspect generated arenaku_stage2_test_* resources.\n");
            $exit = 1;
        }
    }
}
exit($exit);
