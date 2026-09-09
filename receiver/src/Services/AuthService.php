<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use App\Validators\AuthValidator;

class AuthService
{
    private static string $dataPath;
    private static string $resetTokensPath;
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCK_DURATION = 3 * 60;
    private static function paths(): void
    {
        self::$dataPath = __DIR__ . '/../../data/admin.json';
        self::$resetTokensPath = __DIR__ . '/../../data/reset_tokens.json';
    }

    private static function ensureAdminFile(): void
    {
        self::paths();

        if (file_exists(self::$dataPath)) {
            return;
        }

        $dir = dirname(self::$dataPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $initial = [
            'username' => $_ENV['ADMIN_USERNAME'],
            'passwordHash' => password_hash($_ENV['ADMIN_PASSWORD'], PASSWORD_BCRYPT),
            'email' => $_ENV['ADMIN_EMAIL'],

            'failedAttempts' => 0,
            'lockedUntil' => 0,
        ];

        file_put_contents(self::$dataPath, json_encode($initial, JSON_PRETTY_PRINT));
    }

    private static function readAdmin(): array
    {
        self::ensureAdminFile();
        return json_decode(file_get_contents(self::$dataPath), true);
    }

    private static function writeAdmin(array $data): void
    {
        file_put_contents(self::$dataPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private static function readResetTokens(): array
    {
        self::paths();
        if (!file_exists(self::$resetTokensPath)) {
            return [];
        }
        return json_decode(file_get_contents(self::$resetTokensPath), true) ?? [];
    }

    private static function writeResetTokens(array $tokens): void
    {
        file_put_contents(self::$resetTokensPath, json_encode($tokens, JSON_PRETTY_PRINT));
    }

    public static function login(string $username, string $password): string
    {
        $admin = self::readAdmin();

        self::ensureNotLocked($admin);

        if (
            $username !== $admin['username'] ||
            !password_verify($password, $admin['passwordHash'])
        ) {
            self::recordFailedLogin($admin);

            usleep(500000);

            throw new Exception('Username atau password salah');
        }

        self::resetFailedLogin($admin);

        return self::generateJwt($admin['username']);
    }

    public static function changePassword(
        string $username,
        string $oldPassword,
        string $newPassword
    ): void {
        $admin = self::readAdmin();

        if ($username !== $admin['username']) {
            throw new Exception('Tidak diizinkan');
        }

        if (!password_verify($oldPassword, $admin['passwordHash'])) {
            throw new Exception('Password lama salah');
        }

        AuthValidator::validatePassword($newPassword);

        $admin['passwordHash'] = password_hash(
            $newPassword,
            PASSWORD_BCRYPT
        );

        self::writeAdmin($admin);
    }

    public static function generateResetToken(): array
    {
        $admin = self::readAdmin();

        $token = bin2hex(random_bytes(32));
        $expiresAt = (time() + 15 * 60) * 1000;

        $tokens = [];

        $tokens[$token] = [
            'expiresAt' => $expiresAt
        ];

        self::writeResetTokens($tokens);

        return [
            'token' => $token,
            'email' => $admin['email']
        ];
    }

    public static function resetPassword(string $token, string $newPassword): void
    {
        $tokens = self::readResetTokens();
        $entry = $tokens[$token] ?? null;

        if (!$entry || $entry['expiresAt'] < (time() * 1000)) {
            unset($tokens[$token]);
            self::writeResetTokens($tokens);

            throw new Exception(
                'Token reset tidak valid atau sudah kedaluwarsa'
            );
        }

        AuthValidator::validatePassword($newPassword);

        $admin = self::readAdmin();

        $admin['passwordHash'] = password_hash(
            $newPassword,
            PASSWORD_BCRYPT
        );

        self::writeAdmin($admin);

        unset($tokens[$token]);
        self::writeResetTokens($tokens);
    }

    private static function generateJwt(string $username): string
    {
        $expiresInSeconds = self::parseExpiry($_ENV['JWT_EXPIRES_IN'] ?? '8h');

        $payload = [
            'username' => $username,
            'iat' => time(),
            'exp' => time() + $expiresInSeconds,
        ];

        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    public static function verifyToken(string $token): array
    {
        $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
        return (array)$decoded;
    }

    private static function parseExpiry(string $value): int
    {
        if (preg_match('/^(\d+)([smhd])$/', $value, $matches)) {
            $number = (int)$matches[1];
            $unit = $matches[2];

            return match ($unit) {
                's' => $number,
                'm' => $number * 60,
                'h' => $number * 3600,
                'd' => $number * 86400,
                default => 28800,
            };
        }

        return (int)$value ?: 28800;
    }

    private static function ensureNotLocked(array &$admin): void
    {
        $lockedUntil = $admin['lockedUntil'] ?? 0;

        if ($lockedUntil === 0) {
            return;
        }

        if ($lockedUntil <= time()) {
            $admin['failedAttempts'] = 0;
            $admin['lockedUntil'] = 0;

            self::writeAdmin($admin);
            return;
        }

        $remaining = $lockedUntil - time();

        throw new Exception(
            'Terlalu banyak percobaan login.',
            $lockedUntil
        );
    }

    private static function recordFailedLogin(array &$admin): void
    {
        $admin['failedAttempts'] = ($admin['failedAttempts'] ?? 0) + 1;

        if ($admin['failedAttempts'] >= self::MAX_LOGIN_ATTEMPTS) {
            $admin['lockedUntil'] = time() + self::LOCK_DURATION;
        }

        self::writeAdmin($admin);
    }

    private static function resetFailedLogin(array &$admin): void
    {
        $admin['failedAttempts'] = 0;
        $admin['lockedUntil'] = 0;

        self::writeAdmin($admin);
    }
}
