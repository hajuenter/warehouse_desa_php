<?php

namespace Tests\Integration\Services;

use App\Config\Database;
use App\Services\DesaConnectionService;
use App\Services\QueueMonitorService;
use Dotenv\Dotenv;
use Exception;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class QueueMonitorServiceTest extends TestCase
{
    private PDO $mainPdo;
    private PDO $testPdo;
    private string $testDbName;
    private string $kodeDesa = 'DS001';

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
            CREATE TABLE IF NOT EXISTS desa (
                kode_desa VARCHAR(50) PRIMARY KEY,
                nama_desa VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE'
            ) ENGINE=InnoDB
        ");
        $this->mainPdo->exec("TRUNCATE TABLE desa");
        $this->mainPdo->exec("
            INSERT INTO desa (kode_desa, nama_desa, status)
            VALUES ('{$this->kodeDesa}', 'Desa Test', 'ACTIVE')
        ");

        $this->mainPdo->exec("
            CREATE TABLE IF NOT EXISTS inbound_queue (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(40) NULL,
                kode_desa VARCHAR(10) NOT NULL,
                table_name VARCHAR(100) NOT NULL,
                action VARCHAR(20) NOT NULL,
                record_id INT NULL,
                payload LONGTEXT,
                status ENUM('RECEIVED', 'PROCESSING', 'SUCCESS', 'FAILED') NOT NULL DEFAULT 'RECEIVED',
                error_message TEXT NULL,
                received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL,
                INDEX idx_desa_table (kode_desa, table_name),
                INDEX idx_event (event_id),
                INDEX idx_status (status)
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

        parent::tearDown();
    }

    /*----------------------------------------------------------------------+
    |  getQueue()                                                           |
    +----------------------------------------------------------------------*/

    public function testGetQueueReturnsAllRowsWhenNoStatusFilter(): void
    {
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('SUCCESS');

        $result = QueueMonitorService::getQueue($this->kodeDesa);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testGetQueueFiltersByStatus(): void
    {
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('SUCCESS');

        $result = QueueMonitorService::getQueue($this->kodeDesa, 'RECEIVED');

        $this->assertCount(2, $result);

        foreach ($result as $row) {
            $this->assertEquals('RECEIVED', $row['status']);
        }
    }

    public function testGetQueueReturnsKodeDesaInEachRow(): void
    {
        $this->insertLogItem('RECEIVED');

        $result = QueueMonitorService::getQueue($this->kodeDesa);

        $this->assertCount(1, $result);
        $this->assertEquals($this->kodeDesa, $result[0]['kode_desa']);
    }

    public function testGetQueueDecodesPayloadFromJson(): void
    {
        $payload = ['nama' => 'Budi', 'nik' => '3301234567890001'];
        $this->insertLogItem('RECEIVED', $payload);

        $result = QueueMonitorService::getQueue($this->kodeDesa);

        $this->assertCount(1, $result);
        $this->assertIsArray($result[0]['payload']);
        $this->assertEquals('Budi', $result[0]['payload']['nama']);
        $this->assertEquals('3301234567890001', $result[0]['payload']['nik']);
    }

    public function testGetQueueRespectsLimitAndOffset(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->insertLogItem('RECEIVED');
        }

        $result = QueueMonitorService::getQueue($this->kodeDesa, null, 3, 2);

        $this->assertCount(3, $result);
    }

    public function testGetQueueReturnsEmptyArrayWhenNoData(): void
    {
        $result = QueueMonitorService::getQueue($this->kodeDesa);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetQueueReturnsResultsInDescendingIdOrder(): void
    {
        $id1 = $this->insertLogItem('RECEIVED');
        $id2 = $this->insertLogItem('SUCCESS');
        $id3 = $this->insertLogItem('FAILED');

        $result = QueueMonitorService::getQueue($this->kodeDesa);

        $this->assertCount(3, $result);
        $this->assertGreaterThan($result[1]['id'], $result[0]['id']);
        $this->assertGreaterThan($result[2]['id'], $result[1]['id']);
    }

    /*----------------------------------------------------------------------+
    |  getSummary()                                                         |
    +----------------------------------------------------------------------*/

    public function testGetSummaryReturnsZeroCountsWhenEmpty(): void
    {
        $result = QueueMonitorService::getSummary($this->kodeDesa);

        $this->assertIsArray($result);
        $this->assertEquals($this->kodeDesa, $result['kode_desa']);
        $this->assertEquals(0, $result['pending']);
        $this->assertEquals(0, $result['retry']);
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(0, $result['failed']);
    }

    public function testGetSummaryCountsByStatus(): void
    {
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('RECEIVED');
        $this->insertLogItem('SUCCESS');
        $this->insertLogItem('SUCCESS');
        $this->insertLogItem('FAILED');

        $result = QueueMonitorService::getSummary($this->kodeDesa);

        $this->assertEquals(3, $result['pending']);
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(1, $result['failed']);
        $this->assertEquals(0, $result['retry']);
    }

    public function testGetSummaryIncludesKodeDesa(): void
    {
        $result = QueueMonitorService::getSummary($this->kodeDesa);

        $this->assertArrayHasKey('kode_desa', $result);
        $this->assertEquals($this->kodeDesa, $result['kode_desa']);
    }

    /*----------------------------------------------------------------------+
    |  getSummaryAllDesa()                                                  |
    +----------------------------------------------------------------------*/

    public function testGetSummaryAllDesaReturnsEmptyArrayWhenNoDesa(): void
    {
        $this->testPdo->exec("TRUNCATE TABLE desa");

        $result = QueueMonitorService::getSummaryAllDesa();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetSummaryAllDesaReturnsOnlineStatusForEachDesa(): void
    {
        $result = QueueMonitorService::getSummaryAllDesa();

        $this->assertIsArray($result);
        $this->assertGreaterThanOrEqual(1, count($result));

        $desaResult = $result[0];
        $this->assertEquals($this->kodeDesa, $desaResult['kode_desa']);
        $this->assertTrue($desaResult['online']);
        $this->assertArrayHasKey('nama_desa', $desaResult);
        $this->assertArrayHasKey('pending', $desaResult);
        $this->assertArrayHasKey('success', $desaResult);
        $this->assertArrayHasKey('failed', $desaResult);
    }

    /*----------------------------------------------------------------------+
    |  Helpers                                                              |
    +----------------------------------------------------------------------*/

    private function insertLogItem(string $status, array $payload = []): int
    {
        if (empty($payload)) {
            $payload = ['table' => 'penduduk', 'action' => 'CREATE'];
        }

        $stmt = $this->testPdo->prepare("
            INSERT INTO inbound_queue (kode_desa, table_name, action, record_id, payload, status)
            VALUES (?, 'penduduk', 'INSERT', 999, ?, ?)
        ");
        $stmt->execute([$this->kodeDesa, json_encode($payload), $status]);
        return (int) $this->testPdo->lastInsertId();
    }
}
