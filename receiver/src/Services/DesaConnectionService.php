<?php

namespace App\Services;

use App\Config\Database;
use Exception;

class DesaConnectionService
{
    public static function getAllActiveDesa(): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query(
            "SELECT kode_desa, nama_desa FROM desa
             WHERE status = 'ACTIVE'
             ORDER BY nama_desa"
        );

        return $stmt->fetchAll();
    }

    public static function assertDesaActive(string $kodeDesa): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT kode_desa FROM desa WHERE kode_desa = ? AND status = 'ACTIVE'"
        );
        $stmt->execute([$kodeDesa]);

        if (!$stmt->fetch()) {
            throw new Exception("Desa '{$kodeDesa}' tidak ditemukan atau tidak aktif");
        }
    }
}
