<?php

chdir(dirname(__DIR__, 2));
require 'vendor/autoload.php';
define('ARENAKU_TEST_DATABASE', getenv('DB_DATABASE'));
define('ARENAKU_TEST_DATABASE_USER', getenv('DB_USERNAME'));
try {
    $app = require 'bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    Tests\Support\TestDatabase::assertSafe($app);
    $p = json_decode(base64_decode($argv[1]), true, 512, JSON_THROW_ON_ERROR);
    Carbon\CarbonImmutable::setTestNow(Carbon\CarbonImmutable::parse($p['now'], 'Asia/Jakarta'));
    while (microtime(true) < $p['start']) { usleep(10000); }
    $service = app(App\Services\HourlyBooking::class);
    if ($p['action'] === 'reserve') {
        $result = $service->reserve($p['venue'], [$p['slot']], 'Race fixture', '0800000000', $p['key'], str_repeat('race-scope', 5));
        echo json_encode(['ok' => true, 'id' => $result->id]);
    } elseif ($p['action'] === 'confirm') {
        $result = $service->confirm(App\Models\User::findOrFail($p['actor']), $p['booking']);
        echo json_encode(['ok' => true, 'status' => $result->status]);
    } elseif ($p['action'] === 'expire') {
        echo json_encode(['ok' => true, 'expired' => $service->expireForVenue($p['venue'])]);
    } else { throw new RuntimeException('Unsupported worker operation'); }
} catch (Illuminate\Validation\ValidationException $e) {
    echo json_encode(['ok' => false, 'conflict' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => get_class($e)]);
    exit(1);
}
