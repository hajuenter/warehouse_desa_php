<?php

namespace App\Services;

use App\Config\Database;
use Exception;

class WarehouseService
{
    private static array $tableMap = [
        'config' => [
            'warehouseTable' => 'dw_config',
            'columns' => [
                'app_key',
                'nama_desa',
                'kode_desa_bps',
                'kode_pos',
                'nama_kecamatan',
                'kode_kecamatan',
                'nama_kepala_camat',
                'nip_kepala_camat',
                'nama_kabupaten',
                'kode_kabupaten',
                'nama_propinsi',
                'kode_propinsi',
                'logo',
                'lat',
                'lng',
                'alamat_kantor',
                'telepon',
                'kantor_desa',
            ],
        ],
        'user' => [
            'warehouseTable' => 'dw_user',
            'columns' => ['username', 'id_grup', 'pamong_id', 'email', 'last_login', 'active', 'nama', 'phone', 'foto'],
        ],
        'kategori' => [
            'warehouseTable' => 'dw_kategori',
            'columns' => ['kategori', 'tipe', 'urut', 'enabled', 'parent', 'slug'],
        ],
        'artikel' => [
            'warehouseTable' => 'dw_artikel',
            'columns' => [
                'gambar',
                'isi',
                'enabled',
                'tgl_upload',
                'id_kategori',
                'id_user',
                'judul',
                'gambar1',
                'gambar2',
                'gambar3',
                'dokumen',
                'link_dokumen',
                'boleh_komentar',
                'slug',
                'hit',
                'slider',
            ],
        ],
        'komentar' => [
            'warehouseTable' => 'dw_komentar',
            'columns' => [
                'id_artikel',
                'owner',
                'email',
                'subjek',
                'komentar',
                'tgl_upload',
                'status',
                'tipe',
                'no_hp',
                'is_archived',
            ],
        ],
        // Tabel baru cukup ditambahkan di sini, tidak perlu ubah kode lain
    ];

    public static function apply(array $params): void
    {
        $kodeDesa = $params['kode_desa'];
        $tableName = $params['table_name'];
        $action = $params['action'];
        $recordId = $params['record_id'];
        $payload = $params['payload'];

        $mapping = self::$tableMap[$tableName] ?? null;

        if (!$mapping) {
            throw new Exception("Tabel '{$tableName}' belum dipetakan di WarehouseService");
        }

        if ($action === 'DELETE') {
            self::hardDelete($mapping, $kodeDesa, $recordId);
            return;
        }

        if ($action === 'INSERT') {
            self::upsert($mapping, $kodeDesa, $recordId, $payload);
            return;
        }

        if ($action === 'UPDATE') {
            $dataForWarehouse = (isset($payload['old']) && isset($payload['new']))
                ? $payload['new']
                : $payload;
            self::upsert($mapping, $kodeDesa, $recordId, $dataForWarehouse);
            return;
        }

        throw new Exception("Action '{$action}' tidak dikenali");
    }

    private static function upsert(
        array $mapping,
        string $kodeDesa,
        int|string $recordId,
        array $payload
    ): void {
        $warehouseTable = $mapping['warehouseTable'];
        $columns = $mapping['columns'];

        $values = array_map(fn($col) => $payload[$col] ?? null, $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        $updateClause = implode(', ', array_map(fn($col) => "{$col}=VALUES({$col})", $columns));
        $columnsList = implode(', ', $columns);

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO {$warehouseTable} (kode_desa, source_id, {$columnsList})
            VALUES (?, ?, {$placeholders})
            ON DUPLICATE KEY UPDATE {$updateClause}
        ");

        $stmt->execute([$kodeDesa, $recordId, ...$values]);
    }

    private static function hardDelete(
        array $mapping,
        string $kodeDesa,
        int|string $recordId
    ): void {
        $warehouseTable = $mapping['warehouseTable'];

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("DELETE FROM {$warehouseTable} WHERE kode_desa = ? AND source_id = ?");
        $stmt->execute([$kodeDesa, $recordId]);
    }
}
