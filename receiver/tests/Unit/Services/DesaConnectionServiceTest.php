<?php

namespace Tests\Unit\Services;

use App\Config\Database;
use App\Services\DesaConnectionService;
use PHPUnit\Framework\TestCase;
use PDO;
use ReflectionClass;

class DesaConnectionServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $pdoOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        try {
            $pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};port={$_ENV['DB_PORT']};charset=utf8mb4",
                $_ENV['DB_USER'],
                $_ENV['DB_PASSWORD'] ?? '',
                $pdoOptions
            );

            $reflection = new ReflectionClass(Database::class);
            $prop = $reflection->getProperty('connection');
            $prop->setAccessible(true);
            $prop->setValue(null, $pdo);
        } catch (\Exception $e) {
            $this->markTestSkipped('MySQL tidak tersedia: ' . $e->getMessage());
        }
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Database::class);
        $prop = $reflection->getProperty('connection');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        parent::tearDown();
    }

    public function testGetAllActiveDesaReturnsArray(): void
    {
        $result = DesaConnectionService::getAllActiveDesa();

        $this->assertIsArray($result);
    }
}
