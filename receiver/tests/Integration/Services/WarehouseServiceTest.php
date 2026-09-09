<?php

namespace Tests\Integration\Services;

use App\Config\Database;
use App\Services\WarehouseService;
use Dotenv\Dotenv;
use Exception;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class WarehouseServiceTest extends TestCase
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
            CREATE TABLE IF NOT EXISTS dw_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                kode_desa VARCHAR(50) NOT NULL,
                source_id VARCHAR(100) NOT NULL,
                app_key VARCHAR(255) NULL,
                nama_desa VARCHAR(255) NULL,
                kode_desa_bps VARCHAR(50) NULL,
                kode_pos VARCHAR(10) NULL,
                nama_kecamatan VARCHAR(255) NULL,
                kode_kecamatan VARCHAR(50) NULL,
                nama_kepala_camat VARCHAR(255) NULL,
                nip_kepala_camat VARCHAR(50) NULL,
                nama_kabupaten VARCHAR(255) NULL,
                kode_kabupaten VARCHAR(50) NULL,
                nama_propinsi VARCHAR(255) NULL,
                kode_propinsi VARCHAR(50) NULL,
                logo TEXT NULL,
                lat VARCHAR(50) NULL,
                lng VARCHAR(50) NULL,
                alamat_kantor TEXT NULL,
                telepon VARCHAR(50) NULL,
                kantor_desa TEXT NULL,
                UNIQUE KEY unique_desa_source (kode_desa, source_id)
            ) ENGINE=InnoDB
        ");
        $this->mainPdo->exec("TRUNCATE TABLE dw_config");

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
    |  apply() — INSERT                                                     |
    +----------------------------------------------------------------------*/

    public function testApplyInsertCreatesNewRecord(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => [
                'app_key'    => 'APP001',
                'nama_desa'  => 'Desa Sukamaju',
                'kode_pos'   => '50000',
                'telepon'    => '0271-123456',
            ],
        ]);

        $row = $this->getRow('DS001', '1');

        $this->assertNotNull($row);
        $this->assertEquals('APP001', $row['app_key']);
        $this->assertEquals('Desa Sukamaju', $row['nama_desa']);
        $this->assertEquals('50000', $row['kode_pos']);
        $this->assertEquals('0271-123456', $row['telepon']);
    }

    public function testApplyInsertSetsNullForMissingPayloadKeys(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => ['nama_desa' => 'Desa Test'],
        ]);

        $row = $this->getRow('DS001', '1');

        $this->assertNotNull($row);
        $this->assertEquals('Desa Test', $row['nama_desa']);
        $this->assertNull($row['app_key']);
        $this->assertNull($row['kode_pos']);
    }

    /*----------------------------------------------------------------------+
    |  apply() — UPDATE                                                     |
    +----------------------------------------------------------------------*/

    public function testApplyUpdateModifiesExistingRecord(): void
    {
        $this->insertRow('DS001', '1', ['nama_desa' => 'Nama Lama']);

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'UPDATE',
            'record_id'  => '1',
            'payload'    => ['nama_desa' => 'Nama Baru'],
        ]);

        $row = $this->getRow('DS001', '1');

        $this->assertEquals('Nama Baru', $row['nama_desa']);
    }

    public function testApplyUpdateWithOldNewPayloadUsesNew(): void
    {
        $this->insertRow('DS001', '1', ['nama_desa' => 'Sebelum']);

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'UPDATE',
            'record_id'  => '1',
            'payload'    => [
                'old' => ['nama_desa' => 'Sebelum'],
                'new' => ['nama_desa' => 'Sesudah'],
            ],
        ]);

        $row = $this->getRow('DS001', '1');

        $this->assertEquals('Sesudah', $row['nama_desa']);
    }

    public function testApplyUpdateUpsertsWhenRecordNotExists(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'UPDATE',
            'record_id'  => '999',
            'payload'    => ['nama_desa' => 'Baru Via Update'],
        ]);

        $row = $this->getRow('DS001', '999');

        $this->assertNotNull($row);
        $this->assertEquals('Baru Via Update', $row['nama_desa']);
    }

    /*----------------------------------------------------------------------+
    |  apply() — DELETE                                                     |
    +----------------------------------------------------------------------*/

    public function testApplyDeleteRemovesRecord(): void
    {
        $this->insertRow('DS001', '1', ['nama_desa' => 'Hapus Saya']);

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'DELETE',
            'record_id'  => '1',
            'payload'    => [],
        ]);

        $row = $this->getRow('DS001', '1');

        $this->assertNull($row);
    }

    public function testApplyDeleteNonexistentRecordDoesNotThrow(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'DELETE',
            'record_id'  => '999',
            'payload'    => [],
        ]);

        $this->assertTrue(true);
    }

    /*----------------------------------------------------------------------+
    |  apply() — ERROR HANDLING                                             |
    +----------------------------------------------------------------------*/

    public function testApplyWithUnmappedTableThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Tabel 'unmapped_table' belum dipetakan di WarehouseService");

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'unmapped_table',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => [],
        ]);
    }

    public function testApplyWithUnknownActionThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Action 'TRUNCATE' tidak dikenali");

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'TRUNCATE',
            'record_id'  => '1',
            'payload'    => [],
        ]);
    }

    /*----------------------------------------------------------------------+
    |  apply() — MULTIPLE OPERATIONS                                        |
    +----------------------------------------------------------------------*/

    public function testApplyInsertThenDeleteRemovesRecord(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => ['nama_desa' => 'Sementara'],
        ]);

        $this->assertNotNull($this->getRow('DS001', '1'));

        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'DELETE',
            'record_id'  => '1',
            'payload'    => [],
        ]);

        $this->assertNull($this->getRow('DS001', '1'));
    }

    public function testApplyInsertMultipleDesaIsolation(): void
    {
        WarehouseService::apply([
            'kode_desa'  => 'DS001',
            'table_name' => 'config',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => ['nama_desa' => 'Desa 1'],
        ]);

        WarehouseService::apply([
            'kode_desa'  => 'DS002',
            'table_name' => 'config',
            'action'     => 'INSERT',
            'record_id'  => '1',
            'payload'    => ['nama_desa' => 'Desa 2'],
        ]);

        $row1 = $this->getRow('DS001', '1');
        $row2 = $this->getRow('DS002', '1');

        $this->assertEquals('Desa 1', $row1['nama_desa']);
        $this->assertEquals('Desa 2', $row2['nama_desa']);
    }

    /*----------------------------------------------------------------------+
    |  Helpers                                                              |
    +----------------------------------------------------------------------*/

    private function getRow(string $kodeDesa, string $sourceId): ?array
    {
        $stmt = $this->testPdo->prepare(
            "SELECT * FROM dw_config WHERE kode_desa = ? AND source_id = ?"
        );
        $stmt->execute([$kodeDesa, $sourceId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function insertRow(string $kodeDesa, string $sourceId, array $data): void
    {
        $namaDesa = $data['nama_desa'] ?? null;
        $appKey = $data['app_key'] ?? null;
        $kodePos = $data['kode_pos'] ?? null;

        $stmt = $this->testPdo->prepare("
            INSERT INTO dw_config (kode_desa, source_id, app_key, nama_desa, kode_pos)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$kodeDesa, $sourceId, $appKey, $namaDesa, $kodePos]);
    }
}
