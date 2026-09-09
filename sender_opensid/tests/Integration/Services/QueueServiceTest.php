<?php

namespace Tests\Integration\Services;

use App\Config\Database;
use App\Services\QueueService;
use Dotenv\Dotenv;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class QueueServiceTest extends TestCase
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
            CREATE TABLE IF NOT EXISTS webhook_queue (
                id INT AUTO_INCREMENT PRIMARY KEY,
                event_id VARCHAR(255) NULL,
                table_name VARCHAR(100) NOT NULL,
                event_type VARCHAR(50) NOT NULL,
                record_id VARCHAR(100) NOT NULL,
                payload TEXT NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'pending',
                retry_count INT NOT NULL DEFAULT 0,
                error_message TEXT NULL,
                received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processed_at TIMESTAMP NULL
            ) ENGINE=InnoDB
        ");
        $this->mainPdo->exec("TRUNCATE TABLE webhook_queue");

        $this->testPdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};dbname={$this->testDbName};charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASSWORD'] ?? '',
            $pdoOptions
        );

        /*
         * Reset Database singleton lalu override dengan test PDO.
         */
        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, $this->testPdo);
    }

    protected function tearDown(): void
    {
        $this->mainPdo->exec("DROP DATABASE IF EXISTS `{$this->testDbName}`");

        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /*----------------------------------------------------------------------+
    |  getPendingQueues()                                                   |
    +----------------------------------------------------------------------*/

    public function testGetPendingQueuesReturnsPendingRows(): void
    {
        $this->insertQueue('pending');
        $this->insertQueue('pending');

        $result = QueueService::getPendingQueues();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function testGetPendingQueuesReturnsRetryRows(): void
    {
        $this->insertQueue('retry');

        $result = QueueService::getPendingQueues();

        $this->assertCount(1, $result);
        $this->assertEquals('retry', $result[0]['status']);
    }

    public function testGetPendingQueuesExcludesSuccessRows(): void
    {
        $this->insertQueue('success');
        $this->insertQueue('pending');

        $result = QueueService::getPendingQueues();

        $this->assertCount(1, $result);
        $this->assertEquals('pending', $result[0]['status']);
    }

    public function testGetPendingQueuesExcludesFailedRows(): void
    {
        $this->insertQueue('failed');
        $this->insertQueue('pending');

        $result = QueueService::getPendingQueues();

        $this->assertCount(1, $result);
    }

    public function testGetPendingQueuesRespectsLimit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->insertQueue('pending');
        }

        $result = QueueService::getPendingQueues(3);

        $this->assertCount(3, $result);
    }

    public function testGetPendingQueuesReturnsInAscendingIdOrder(): void
    {
        $id1 = $this->insertQueue('pending');
        $id2 = $this->insertQueue('pending');
        $id3 = $this->insertQueue('pending');

        $result = QueueService::getPendingQueues();

        $this->assertCount(3, $result);
        $this->assertLessThan($result[1]['id'], $result[0]['id']);
        $this->assertLessThan($result[2]['id'], $result[1]['id']);
    }

    public function testGetPendingQueuesReturnsEmptyArrayWhenNoData(): void
    {
        $result = QueueService::getPendingQueues();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testGetPendingQueuesDefaultLimitIsTen(): void
    {
        for ($i = 0; $i < 15; $i++) {
            $this->insertQueue('pending');
        }

        $result = QueueService::getPendingQueues();

        $this->assertCount(10, $result);
    }

    /*----------------------------------------------------------------------+
    |  markSuccess()                                                        |
    +----------------------------------------------------------------------*/

    public function testMarkSuccessUpdatesStatusToSuccess(): void
    {
        $id = $this->insertQueue('pending');

        QueueService::markSuccess($id);

        $row = $this->getQueueById($id);
        $this->assertEquals('success', $row['status']);
    }

    public function testMarkSuccessSetsProcessedAt(): void
    {
        $id = $this->insertQueue('pending');

        QueueService::markSuccess($id);

        $row = $this->getQueueById($id);
        $this->assertNotNull($row['processed_at']);
    }

    public function testMarkSuccessOnRetryRow(): void
    {
        $id = $this->insertQueue('retry');

        QueueService::markSuccess($id);

        $row = $this->getQueueById($id);
        $this->assertEquals('success', $row['status']);
    }

    /*----------------------------------------------------------------------+
    |  markFailed()                                                         |
    +----------------------------------------------------------------------*/

    public function testMarkFailedSetsStatusToRetryWhenBelowMaxRetry(): void
    {
        $_ENV['MAX_RETRY'] = '5';
        $id = $this->insertQueue('pending', 0);

        QueueService::markFailed($id, 'Connection timeout');

        $row = $this->getQueueById($id);
        $this->assertEquals('retry', $row['status']);
    }

    public function testMarkFailedIncrementsRetryCount(): void
    {
        $_ENV['MAX_RETRY'] = '5';
        $id = $this->insertQueue('pending', 2);

        QueueService::markFailed($id, 'Error');

        $row = $this->getQueueById($id);
        $this->assertEquals(3, $row['retry_count']);
    }

    public function testMarkFailedSetsStatusToFailedWhenMaxRetryReached(): void
    {
        $_ENV['MAX_RETRY'] = '3';
        $id = $this->insertQueue('pending', 2);

        QueueService::markFailed($id, 'Max retries exceeded');

        $row = $this->getQueueById($id);
        $this->assertEquals('failed', $row['status']);
    }

    public function testMarkFailedSetsProcessedAtWhenFailed(): void
    {
        $_ENV['MAX_RETRY'] = '1';
        $id = $this->insertQueue('pending', 0);

        QueueService::markFailed($id, 'Error');

        $row = $this->getQueueById($id);
        $this->assertNotNull($row['processed_at']);
    }

    public function testMarkFailedSetsErrorMessage(): void
    {
        $_ENV['MAX_RETRY'] = '5';
        $id = $this->insertQueue('pending', 0);
        $errorMsg = 'Gagal kirim webhook: timeout';

        QueueService::markFailed($id, $errorMsg);

        $row = $this->getQueueById($id);
        $this->assertEquals($errorMsg, $row['error_message']);
    }

    public function testMarkFailedOnNonexistentIdDoesNotThrow(): void
    {
        QueueService::markFailed(9999, 'Error');

        $this->assertTrue(true);
    }

    public function testMarkFailedMultipleTimesIncreasesRetryCount(): void
    {
        $_ENV['MAX_RETRY'] = '10';
        $id = $this->insertQueue('pending', 0);

        QueueService::markFailed($id, 'Error 1');
        QueueService::markFailed($id, 'Error 2');
        QueueService::markFailed($id, 'Error 3');

        $row = $this->getQueueById($id);
        $this->assertEquals(3, $row['retry_count']);
        $this->assertEquals('retry', $row['status']);
    }

    public function testMarkFailedUsesDefaultMaxRetryOfFive(): void
    {
        unset($_ENV['MAX_RETRY']);
        $id = $this->insertQueue('pending', 4);

        QueueService::markFailed($id, 'Error');

        $row = $this->getQueueById($id);
        $this->assertEquals('failed', $row['status']);
    }

    /*----------------------------------------------------------------------+
    |  Helpers                                                              |
    +----------------------------------------------------------------------*/

    private function getQueueById(int $id): ?array
    {
        $stmt = $this->testPdo->prepare("SELECT * FROM webhook_queue WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insertQueue(string $status, int $retryCount = 0): int
    {
        $stmt = $this->testPdo->prepare("
            INSERT INTO webhook_queue (table_name, event_type, record_id, payload, status, retry_count)
            VALUES ('penduduk', 'CREATE', '999', '{}', ?, ?)
        ");
        $stmt->execute([$status, $retryCount]);
        return (int) $this->testPdo->lastInsertId();
    }
}
