<?php

namespace Tests\Integration\Services;

use App\Config\Database;
use App\Services\DesaConnectionService;
use Dotenv\Dotenv;
use Exception;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class DesaConnectionServiceTest extends TestCase
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
            CREATE TABLE IF NOT EXISTS desa (
                kode_desa VARCHAR(50) PRIMARY KEY,
                nama_desa VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE'
            ) ENGINE=InnoDB
        ");
        $this->mainPdo->exec("TRUNCATE TABLE desa");

        $this->mainPdo->exec("
            INSERT INTO desa (kode_desa, nama_desa, status) VALUES
            ('DS001', 'Desa Sukamaju', 'ACTIVE'),
            ('DS002', 'Desa Harapan', 'ACTIVE'),
            ('DS003', 'Desa Maju Bersama', 'INACTIVE')
        ");

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
    |  getAllActiveDesa()                                                    |
    +----------------------------------------------------------------------*/

    public function testGetAllActiveDesaReturnsActiveDesa(): void
    {
        $result = DesaConnectionService::getAllActiveDesa();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $kodeDesaList = array_column($result, 'kode_desa');
        $this->assertContains('DS001', $kodeDesaList);
        $this->assertContains('DS002', $kodeDesaList);
    }

    public function testGetAllActiveDesaExcludesInactiveDesa(): void
    {
        $result = DesaConnectionService::getAllActiveDesa();

        $kodeDesaList = array_column($result, 'kode_desa');
        $this->assertNotContains('DS003', $kodeDesaList);
    }

    public function testGetAllActiveDesaReturnsSortedByName(): void
    {
        $result = DesaConnectionService::getAllActiveDesa();

        $this->assertCount(2, $result);
        $this->assertEquals('Desa Harapan', $result[0]['nama_desa']);
        $this->assertEquals('Desa Sukamaju', $result[1]['nama_desa']);
    }

    /*----------------------------------------------------------------------+
    |  assertDesaActive()                                                   |
    +----------------------------------------------------------------------*/

    public function testAssertDesaActiveWithValidActiveDesaDoesNotThrow(): void
    {
        DesaConnectionService::assertDesaActive('DS001');

        $this->assertTrue(true);
    }

    public function testAssertDesaActiveWithInactiveDesaThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Desa 'DS003' tidak ditemukan atau tidak aktif");

        DesaConnectionService::assertDesaActive('DS003');
    }

    public function testAssertDesaActiveWithNonexistentDesaThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Desa 'DS999' tidak ditemukan atau tidak aktif");

        DesaConnectionService::assertDesaActive('DS999');
    }
}
