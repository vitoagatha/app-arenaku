<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

if (!defined('ARENAKU_TEST_DATABASE')) {
    throw new RuntimeException('Use php tests/run.php to provision an isolated MySQL database first.');
}
