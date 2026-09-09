<?php

namespace App\Controllers;

use App\Services\LogService;
use App\Services\WarehouseService;
use Exception;

class WebhookController
{
    public static function receive(array $body, array $desa): void
    {
        $eventId = $body['event_id'] ?? null;
        $table = $body['table'] ?? null;
        $action = $body['action'] ?? null;
        $recordId = $body['record_id'] ?? null;
        $payload = $body['payload'] ?? null;
        $kodeDesa = $desa['kode_desa'];

        if (!$table || !$action || $recordId === null) {
            http_response_code(400);
            echo json_encode(['message' => 'Field table, action, record_id wajib diisi']);
            return;
        }

        $logId = LogService::record([
            'eventId' => $eventId,
            'kode_desa' => $kodeDesa,
            'table_name' => $table,
            'action' => $action,
            'record_id' => $recordId,
            'payload' => $payload,
        ]);

        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['message' => 'Diterima', 'log_id' => $logId]);

        self::closeConnectionAndContinue();

        try {
            WarehouseService::apply([
                'kode_desa' => $kodeDesa,
                'table_name' => $table,
                'action' => $action,
                'record_id' => $recordId,
                'payload' => $payload,
            ]);
            LogService::markProcessed($logId);
            error_log("[{$kodeDesa}] {$action} {$table} #{$recordId} -> SUCCESS");
        } catch (Exception $e) {
            LogService::markFailed($logId, $e->getMessage());
            error_log("[{$kodeDesa}] {$action} {$table} #{$recordId} -> FAILED: {$e->getMessage()}");
        }
    }

    private static function closeConnectionAndContinue(): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            return;
        }

        ignore_user_abort(true);
        header('Connection: close');
        ob_end_flush();
        flush();
    }
}
