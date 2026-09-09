<?php

namespace App\Controllers;

use App\Config\Database;

class MetaController
{
    public static function listDesa(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT kode_desa, nama_desa FROM desa WHERE status = 'ACTIVE' ORDER BY nama_desa");
        $rows = $stmt->fetchAll();

        echo json_encode(['data' => $rows]);
    }

    public static function listTables(): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT DISTINCT table_name FROM inbound_queue ORDER BY table_name");
        $rows = $stmt->fetchAll();

        $tableNames = array_map(fn($r) => $r['table_name'], $rows);

        echo json_encode(['data' => $tableNames]);
    }
}