<?php

namespace App\Controllers;

use App\Config\Database;
use Throwable;

class HealthController
{
    public static function check(): void
    {
        $host = $_ENV['DB_HOST'] ?? null;
        $port = $_ENV['DB_PORT'] ?? null;
        $name = $_ENV['DB_NAME'] ?? null;
        $user = $_ENV['DB_USER'] ?? null;

        $database = [
            'connected' => false,
            'host' => $host,
            'port' => $port,
            'name' => $name,
            'user' => $user,
            'error' => null,
        ];

        try {
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1');

            $database['connected'] = true;
            $database['serverVersion'] = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        } catch (Throwable $e) {
            $database['error'] = $e->getMessage();

            http_response_code(503);

            echo json_encode([
                'status' => 'error',
                'message' => 'Receiver berjalan, tetapi gagal terkoneksi ke database',
                'database' => $database,
            ]);

            return;
        }

        echo json_encode([
            'status' => 'ok',
            'message' => 'Receiver berhasil berjalan',
            'database' => $database,
        ]);
    }
}
