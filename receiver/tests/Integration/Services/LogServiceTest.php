<?php

namespace Tests\Integration\Services;

use App\Config\Database;
use App\Services\LogService;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class LogServiceTest extends TestCase
{
    private PDO $mainPdo;
    private PDO $testPdo;
    private string $testDbName;

    /*----------------------------------------------------------------------+
    |  Setup & Teardown                                                     |
    +----------------------------------------------------------------------*/

    protected function setUp(): void
    {
        $dotenv = Dotenv::createImmutable(dirname(__DIR__, 3));
        $dotenv->load();
        $dotenv->required(['DB_HOST', 'DB_PORT', 'DB_USER', 'DB_NAME']);

        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];

        try {
            $this->mainPdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};charset=utf8mb4",
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'] ?? '',
                $pdoOptions
            );
            $stmt = $this->mainPdo->prepare("SELECT 1");
            $stmt->execute();
            $stmt->fetchAll();
        } catch (\Exception $e) {
            $this->markTestSkipped('MySQL tidak tersedia: ' . $e->getMessage());
        }

        $this->testDbName = $_ENV['DB_NAME'] . '_test';
        $this->mainPdo->exec("CREATE DATABASE IF NOT EXISTS `{$this->testDbName}`");
        $this->mainPdo->exec("USE `{$this->testDbName}`");

        $this->mainPdo->exec("
            CREATE TABLE IF NOT EXISTS inbound_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(255) NULL,
                kode_desa VARCHAR(50) NOT NULL,
                table_name VARCHAR(100) NOT NULL,
                action VARCHAR(50) NOT NULL,
                record_id VARCHAR(100) NOT NULL,
                payload TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'RECEIVED',
                error_message TEXT NULL,
                received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL
            ) ENGINE=InnoDB
        ");
        $this->mainPdo->exec("TRUNCATE TABLE inbound_queue");

        $this->testPdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$this->testDbName};charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'] ?? '',
            $pdoOptions
        );

        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, $this->testPdo);
    }

    protected function tearDown(): void
    {
        $this->mainPdo->exec("DROP DATABASE IF EXISTS `{$this->testDbName}`");

        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /*----------------------------------------------------------------------+
    |  record()                                                             |
    +----------------------------------------------------------------------*/

    public function testRecordInsertsLogAndReturnsId(): void
    {
        $params = [
            'eventId'    => 'evt-001',
            'kode_desa'   => 'DS001',
            'table_name'  => 'penduduk',
            'action'      => 'CREATE',
            'record_id'   => '123',
            'payload'     => ['nama' => 'Budi', 'nik' => '3301234567890001'],
        ];

        $logId = LogService::record($params);

        $this->assertIsInt($logId);
        $this->assertGreaterThan(0, $logId);

        $row = $this->getLogById($logId);
        $this->assertEquals('evt-001', $row['event_id']);
        $this->assertEquals('DS001', $row['kode_desa']);
        $this->assertEquals('penduduk', $row['table_name']);
        $this->assertEquals('CREATE', $row['action']);
        $this->assertEquals('123', $row['record_id']);
        $this->assertEquals(json_encode($params['payload']), $row['payload']);
        $this->assertEquals('RECEIVED', $row['status']);
    }

    public function testRecordWithNullEventId(): void
    {
        $params = [
            'kode_desa'  => 'DS002',
            'table_name' => 'mutasi',
            'action'     => 'UPDATE',
            'record_id'  => '456',
            'payload'    => ['status' => 'pindah'],
        ];

        $logId = LogService::record($params);

        $row = $this->getLogById($logId);
        $this->assertNull($row['event_id']);
        $this->assertEquals('DS002', $row['kode_desa']);
    }

    /*----------------------------------------------------------------------+
    |  markProcessed()                                                      |
    +----------------------------------------------------------------------*/

    public function testMarkProcessedUpdatesStatusAndTimestamp(): void
    {
        $logId = $this->insertLog('DS003', 'RECEIVED');

        LogService::markProcessed($logId);

        $row = $this->getLogById($logId);
        $this->assertEquals('SUCCESS', $row['status']);
        $this->assertNotNull($row['processed_at']);
    }

    /*----------------------------------------------------------------------+
    |  markFailed()                                                         |
    +----------------------------------------------------------------------*/

    public function testMarkFailedUpdatesStatusAndError(): void
    {
        $logId = $this->insertLog('DS004', 'RECEIVED');
        $errorMessage = 'Data tidak valid: nik sudah ada';

        LogService::markFailed($logId, $errorMessage);

        $row = $this->getLogById($logId);
        $this->assertEquals('FAILED', $row['status']);
        $this->assertEquals($errorMessage, $row['error_message']);
        $this->assertNotNull($row['processed_at']);
    }

    /*----------------------------------------------------------------------+
    |  Helpers                                                              |
    +----------------------------------------------------------------------*/

    private function getLogById(int $id): ?array
    {
        $stmt = $this->testPdo->prepare("SELECT * FROM inbound_queue WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insertLog(string $kodeDesa, string $status): int
    {
        $stmt = $this->testPdo->prepare("
            INSERT INTO inbound_queue (kode_desa, table_name, action, record_id, payload, status)
            VALUES (?, 'test_table', 'CREATE', '999', '{}', ?)
        ");
        $stmt->execute([$kodeDesa, $status]);
        return (int) $this->testPdo->lastInsertId();
    }
}
