<?php

namespace Tests\Support;

use PDO;
use RuntimeException;

final class IsolatedMysql
{
    private PDO $admin;
    private array $source;
    private string $name;
    private string $user;
    private string $password;
    private bool $created = false;
    private array $accounts = [];
    public array $snapshot = [];
    public string $beforeFingerprint;

    public function __construct()
    {
        $env = \Dotenv\Dotenv::parse(file_get_contents(dirname(__DIR__, 2).'/.env'));
        if (($env['DB_CONNECTION'] ?? 'mysql') !== 'mysql' || !empty($env['DB_URL'])) {
            throw new RuntimeException('Test runner requires an explicit local MySQL configuration.');
        }
        $this->source = [
            'host' => $env['DB_HOST'] ?? '127.0.0.1',
            'port' => $env['DB_PORT'] ?? '3306',
            'database' => $env['DB_DATABASE'] ?? '',
            'username' => $env['DB_USERNAME'] ?? '',
            'password' => $env['DB_PASSWORD'] ?? '',
        ];
        if (!in_array($this->source['host'], ['localhost', '127.0.0.1'], true)
            || !ctype_digit((string) $this->source['port']) || $this->source['database'] === '') {
            throw new RuntimeException('Refusing a non-local or unspecified source database.');
        }
        $suffix = bin2hex(random_bytes(6));
        $this->name = 'arenaku_stage2_test_'.$suffix;
        $this->user = 'arenaku_test_'.$suffix;
        $this->password = bin2hex(random_bytes(24));
        $this->snapshot = $this->readSource();
        $this->beforeFingerprint = hash('sha256', json_encode($this->snapshot));

        // This connection has no default database; only generated test identifiers are used for DDL.
        $this->admin = $this->connect($this->source, false);
    }

    private function connect(array $config, bool $database = true): PDO
    {
        $dsn = 'mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4';
        if ($database) {
            $dsn .= ';dbname='.$config['database'];
        }
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function readSource(): array
    {
        $pdo = $this->connect($this->source);
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->beginTransaction();
        try {
            // No customer names, contacts, account credentials, or sessions are copied.
            $queries = [
                'users' => 'SELECT id,role FROM users ORDER BY id',
                'lapangan' => 'SELECT id,user_id FROM lapangan ORDER BY id',
                'courts' => 'SELECT id,nama_courts FROM courts ORDER BY id',
                'jadwal_lapangan' => 'SELECT id,lapangan_id,court_id,jam_awal,jam_akhir,tanggal,status,created_at,updated_at FROM jadwal_lapangan ORDER BY id',
                'konfirmasi_bookings' => 'SELECT id,jadwal_lapangan_id,durasi,status,created_at,updated_at FROM konfirmasi_bookings ORDER BY id',
            ];
            $result = [];
            foreach ($queries as $table => $sql) {
                $result[$table] = $pdo->query($sql)->fetchAll();
            }
            return $result;
        } finally {
            $pdo->rollBack();
        }
    }

    public function provision(): void
    {
        $this->assertGeneratedTarget();
        $this->admin->exec('CREATE DATABASE '.$this->identifier($this->name).' CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci');
        $this->created = true;
        foreach (['localhost', '127.0.0.1'] as $host) {
            $account = $this->admin->quote($this->user).'@'.$this->admin->quote($host);
            $this->admin->exec('CREATE USER '.$account.' IDENTIFIED BY '.$this->admin->quote($this->password));
            $this->accounts[] = $account;
            $this->admin->exec('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, ALTER, INDEX, REFERENCES ON '
                .$this->identifier($this->name).'.* TO '.$account);
        }

        define('ARENAKU_TEST_DATABASE', $this->name);
        define('ARENAKU_TEST_DATABASE_USER', $this->user);
        $values = [
            'APP_ENV' => 'testing', 'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_URL' => 'http://localhost', 'APP_TIMEZONE' => 'Asia/Jakarta',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $this->source['host'],
            'DB_PORT' => (string) $this->source['port'], 'DB_DATABASE' => $this->name,
            'DB_USERNAME' => $this->user, 'DB_PASSWORD' => $this->password,
            'DB_URL' => '', 'SESSION_DRIVER' => 'array', 'CACHE_DRIVER' => 'array',
            'CACHE_STORE' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
            'BCRYPT_ROUNDS' => '4', 'LOG_CHANNEL' => 'errorlog',
        ];
        $runtime = dirname(__DIR__).'/.runtime/'.$this->name;
        if (!mkdir($runtime.'/views', 0700, true) && !is_dir($runtime.'/views')) {
            throw new RuntimeException('Cannot create isolated test runtime.');
        }
        $values['VIEW_COMPILED_PATH'] = $runtime.'/views';
        $values['APP_CONFIG_CACHE'] = $runtime.'/unused-config.php';
        foreach ($values as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $_SERVER[$key] = $value;
        }

        // Prove the credentials used by the application cannot read the source schema.
        $test = $this->connect([
            'host' => $this->source['host'], 'port' => $this->source['port'],
            'database' => $this->name, 'username' => $this->user, 'password' => $this->password,
        ]);
        try {
            $test->query('SELECT id FROM '.$this->identifier($this->source['database']).'.users LIMIT 1');
        } catch (\PDOException $e) {
            if (in_array((int) ($e->errorInfo[1] ?? 0), [1044, 1142], true)) {
                echo "PASS: test database account cannot access the source schema.\n";
                return;
            }
            throw $e;
        }
        throw new RuntimeException('Test account unexpectedly has access to the source database.');
    }

    public function sourceUnchanged(): bool
    {
        return hash_equals($this->beforeFingerprint, hash('sha256', json_encode($this->readSource())));
    }

    public function cleanup(): void
    {
        $this->assertGeneratedTarget();
        if ($this->created) {
            $this->admin->exec('DROP DATABASE '.$this->identifier($this->name));
            $this->created = false;
        }
        foreach ($this->accounts as $account) {
            $this->admin->exec('DROP USER '.$account);
        }
        $this->accounts = [];
    }

    private function assertGeneratedTarget(): void
    {
        if (!preg_match('/^arenaku_stage2_test_[a-f0-9]{12}$/D', $this->name)
            || !preg_match('/^arenaku_test_[a-f0-9]{12}$/D', $this->user)
            || $this->name === $this->source['database']) {
            throw new RuntimeException('Refusing an unsafe test target.');
        }
    }

    private function identifier(string $value): string
    {
        return chr(96).str_replace(chr(96), chr(96).chr(96), $value).chr(96);
    }
}
