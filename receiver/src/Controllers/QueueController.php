<?php

namespace App\Controllers;

use App\Services\QueueMonitorService;
use Exception;

class QueueController
{
    public static function list(): void
    {
        $kodeDesa = $_GET['kode_desa'] ?? null;
        $status = $_GET['status'] ?? null;
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);

        if (!$kodeDesa) {
            http_response_code(400);
            echo json_encode(['message' => 'Parameter kode_desa wajib diisi']);
            return;
        }

        try {
            $data = QueueMonitorService::getQueue($kodeDesa, $status, $limit, $offset);
            echo json_encode(['data' => $data]);
        } catch (Exception $e) {
            http_response_code(502);
            echo json_encode([
                'message' => "Gagal mengambil antrian desa '{$kodeDesa}'",
                'error' => $e->getMessage(),
            ]);
        }
    }

    public static function summary(): void
    {
        $kodeDesa = $_GET['kode_desa'] ?? null;

        try {
            if ($kodeDesa) {
                $summary = QueueMonitorService::getSummary($kodeDesa);
                echo json_encode(['data' => [$summary]]);
                return;
            }

            $summaries = QueueMonitorService::getSummaryAllDesa();
            echo json_encode(['data' => $summaries]);
        } catch (Exception $e) {
            http_response_code(502);
            echo json_encode([
                'message' => 'Gagal mengambil ringkasan antrian',
                'error' => $e->getMessage(),
            ]);
        }
    }
}