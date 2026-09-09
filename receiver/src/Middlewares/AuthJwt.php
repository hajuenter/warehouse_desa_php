<?php

namespace App\Middlewares;

use App\Services\AuthService;
use Exception;

class AuthJwt
{
    public static function authenticate(): array
    {
        $token = self::getBearerToken();

        if (!$token) {
            self::reject('Token tidak ditemukan');
        }

        try {
            return (array) AuthService::verifyToken($token);
        } catch (Exception $e) {
            self::reject('Token tidak valid atau kedaluwarsa');
        }
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

    private static function reject(string $message): never
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['message' => $message]);
        exit;
    }
}
