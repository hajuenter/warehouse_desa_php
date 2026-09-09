<?php

namespace App\Services;

use App\Config\Database;

class QueueMonitorService
{
    public static function getQueue(string $kodeDesa, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $pdo = Database::getConnection();

        $where = 'WHERE kode_desa = ?';
        $params = [$kodeDesa];

        if ($status) {
            $where .= ' AND status = ?';
            $params[] = $status;
        }

        $sql = "SELECT id, event_id, table_name, action, record_id, payload, status,
                       error_message, received_at, processed_at
                FROM inbound_queue
                {$where}
                ORDER BY id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(function ($row) use ($kodeDesa) {
            $row['kode_desa'] = $kodeDesa;
            $row['payload'] = is_string($row['payload'])
                ? json_decode($row['payload'], true)
                : $row['payload'];
            return $row;
        }, $rows);
    }

    public static function getSummary(string $kodeDesa): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT status, COUNT(*) as total FROM inbound_queue
             WHERE kode_desa = ?
             GROUP BY status"
        );
        $stmt->execute([$kodeDesa]);
        $rows = $stmt->fetchAll();

        $summary = ['pending' => 0, 'retry' => 0, 'success' => 0, 'failed' => 0];
        foreach ($rows as $row) {
            $status = strtolower($row['status']);
            if ($status === 'received') {
                $summary['pending'] = (int)$row['total'];
            } elseif ($status === 'success') {
                $summary['success'] = (int)$row['total'];
            } elseif ($status === 'failed') {
                $summary['failed'] = (int)$row['total'];
            }
        }

        return array_merge(['kode_desa' => $kodeDesa], $summary);
    }

    public static function getSummaryAllDesa(): array
    {
        $desaList = DesaConnectionService::getAllActiveDesa();
        $results = [];

        foreach ($desaList as $desa) {
            try {
                $summary = self::getSummary($desa['kode_desa']);
                $summary['nama_desa'] = $desa['nama_desa'];
                $summary['online'] = true;
                $results[] = $summary;
            } catch (\Exception $e) {
                $results[] = [
                    'kode_desa' => $desa['kode_desa'],
                    'nama_desa' => $desa['nama_desa'],
                    'online' => false,
                    'error' => $e->getMessage(),
                    'pending' => 0,
                    'retry' => 0,
                    'success' => 0,
                    'failed' => 0,
                ];
            }
        }

        return $results;
    }
}
