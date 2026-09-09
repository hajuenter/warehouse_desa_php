<?php

namespace App\Middlewares;

use App\Config\Database;

class AuthDesa
{
    public static function authenticate(): array
    {
        $token = self::getBearerToken();

        if (!$token) {
            self::reject(401, 'Token tidak ditemukan');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            "SELECT id, kode_desa, nama_desa, status FROM desa WHERE api_token = ?"
        );
        $stmt->execute([$token]);
        $desa = $stmt->fetch();

        if (!$desa) {
            self::reject(401, 'Token tidak valid');
        }

        if ($desa['status'] !== 'ACTIVE') {
            self::reject(403, "Desa {$desa['kode_desa']} tidak aktif");
        }

        return $desa;
    }

    private static function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization']
            ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        return str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : null;
    }

    private static function reject(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode(['message' => $message]);
        exit;
    }
}