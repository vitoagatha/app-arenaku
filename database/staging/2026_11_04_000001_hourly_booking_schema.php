<?php

\Tests\Support\TestDatabase::assertSafe(app());

return require dirname(__DIR__).'/cutover/2026_11_04_000001_hourly_booking_schema.php';
