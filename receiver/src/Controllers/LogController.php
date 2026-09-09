<?php

namespace App\Controllers;

use App\Config\Database;

class LogController
{
    public static function list(): void
    {
        $kodeDesa = $_GET['kode_desa'] ?? null;
        $tableName = $_GET['table_name'] ?? null;
        $action = $_GET['action'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        $conditions = [];
        $params = [];

        if ($kodeDesa) {
            $conditions[] = 'kode_desa = ?';
            $params[] = $kodeDesa;
        }
        if ($tableName) {
            $conditions[] = 'table_name = ?';
            $params[] = $tableName;
        }
        if ($action) {
            $conditions[] = 'action = ?';
            $params[] = $action;
        }

        $whereClause = count($conditions) > 0 ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $pdo = Database::getConnection();
        $sql = "SELECT id, event_id, kode_desa, table_name, action, record_id, payload, status, error_message, received_at, processed_at
                FROM inbound_queue
                {$whereClause}
                ORDER BY received_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $parsed = array_map(function ($row) {
            $row['payload'] = json_decode($row['payload'], true);
            return $row;
        }, $rows);

        echo json_encode(['data' => $parsed]);
    }
}