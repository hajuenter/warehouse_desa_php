<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Services\MailService;
use Exception;

class AuthController
{
    public static function login(array $body): void
    {
        $username = $body['username'] ?? null;
        $password = $body['password'] ?? null;

        if (!$username || !$password) {
            http_response_code(400);

            echo json_encode([
                'message' => 'Username dan password wajib diisi'
            ]);

            return;
        }

        try {
            $token = AuthService::login($username, $password);

            echo json_encode([
                'token' => $token
            ]);
        } catch (Exception $e) {
            $lockedUntil = $e->getCode();

            if ($lockedUntil > time()) {
                http_response_code(429);

                echo json_encode([
                    'message' => $e->getMessage(),
                    'lockedUntil' => $lockedUntil,
                ]);

                return;
            }

            http_response_code(401);

            echo json_encode([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function changePassword(array $user, array $body): void
    {
        $oldPassword = $body['oldPassword'] ?? null;
        $newPassword = $body['newPassword'] ?? null;

        if (!$oldPassword || !$newPassword) {
            http_response_code(400);

            echo json_encode([
                'message' => 'Password lama dan password baru wajib diisi'
            ]);

            return;
        }

        try {
            AuthService::changePassword(
                $user['username'],
                $oldPassword,
                $newPassword
            );

            echo json_encode([
                'message' => 'Password berhasil diubah'
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'message' => $e->getMessage()
            ]);
        }
    }

    public static function forgotPassword(): void
    {
        try {
            $result = AuthService::generateResetToken();

            MailService::sendResetPasswordEmail(
                $result['email'],
                $result['token']
            );

            echo json_encode([
                'message' => 'Tautan reset password sudah dikirim ke email admin'
            ]);
        } catch (Exception $e) {
            http_response_code(500);

            echo json_encode([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function resetPassword(array $body): void
    {
        $token = $body['token'] ?? null;
        $newPassword = $body['newPassword'] ?? null;

        if (!$token || !$newPassword) {
            http_response_code(400);

            echo json_encode([
                'message' => 'Token dan password baru wajib diisi'
            ]);

            return;
        }

        try {
            AuthService::resetPassword(
                $token,
                $newPassword
            );

            echo json_encode([
                'message' => 'Password berhasil direset, silakan login'
            ]);
        } catch (Exception $e) {
            http_response_code(400);

            echo json_encode([
                'message' => $e->getMessage()
            ]);
        }
    }
}
