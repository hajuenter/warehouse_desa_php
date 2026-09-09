<?php

namespace App\Services;

use App\Config\Database;

class LogService
{
    public static function record(array $params): int
    {
        $eventId = $params['eventId'] ?? null;
        $kodeDesa = $params['kode_desa'];
        $tableName = $params['table_name'];
        $action = $params['action'];
        $recordId = $params['record_id'];
        $payload = $params['payload'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO inbound_queue (event_id, kode_desa, table_name, action, record_id, payload, status)
            VALUES (?, ?, ?, ?, ?, ?, 'RECEIVED')
        ");
        $stmt->execute([
            $eventId,
            $kodeDesa,
            $tableName,
            $action,
            $recordId,
            json_encode($payload),
        ]);

        return (int) $pdo->lastInsertId();
    }

    public static function markProcessed(int $logId): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE inbound_queue SET status='SUCCESS', processed_at=NOW() WHERE id=?");
        $stmt->execute([$logId]);
    }

    public static function markFailed(int $logId, string $errorMessage): void
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE inbound_queue SET status='FAILED', error_message=?, processed_at=NOW() WHERE id=?");
        $stmt->execute([$errorMessage, $logId]);
    }
}
