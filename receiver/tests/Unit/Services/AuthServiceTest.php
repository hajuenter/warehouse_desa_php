<?php

namespace Tests\Unit\Services;

use App\Services\AuthService;
use Exception;
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    /**
     * File data yang digunakan oleh AuthService.
     */
    private string $adminFile;
    private string $resetTokensFile;

    /**
     * Backup data asli sebelum test.
     */
    private ?string $originalAdminData = null;
    private ?string $originalResetTokensData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $projectRoot = dirname(__DIR__, 3);

        $this->adminFile = $projectRoot . '/data/admin.json';
        $this->resetTokensFile = $projectRoot . '/data/reset_tokens.json';

        $_ENV['ADMIN_USERNAME'] = 'admin';
        $_ENV['ADMIN_PASSWORD'] = 'admin123@';
        $_ENV['ADMIN_EMAIL'] = 'test@example.com';

        $_ENV['JWT_SECRET'] = 'phpunit-test-secret-key';
        $_ENV['JWT_EXPIRES_IN'] = '8h';

        if (file_exists($this->adminFile)) {
            $this->originalAdminData = file_get_contents(
                $this->adminFile
            );
        }

        if (file_exists($this->resetTokensFile)) {
            $this->originalResetTokensData = file_get_contents(
                $this->resetTokensFile
            );
        }

        $dataDirectory = dirname($this->adminFile);

        if (!is_dir($dataDirectory)) {
            mkdir($dataDirectory, 0777, true);
        }

        $passwordHash = password_hash(
            $_ENV['ADMIN_PASSWORD'],
            PASSWORD_BCRYPT
        );

        $admin = [
            'username' => $_ENV['ADMIN_USERNAME'],
            'passwordHash' => $passwordHash,
            'email' => $_ENV['ADMIN_EMAIL'],
            'failedAttempts' => 0,
            'lockedUntil' => 0,
        ];

        file_put_contents(
            $this->adminFile,
            json_encode(
                $admin,
                JSON_PRETTY_PRINT
            )
        );

        /*
         * Reset token dikosongkan setiap test.
         */
        file_put_contents(
            $this->resetTokensFile,
            json_encode([])
        );
    }

    protected function tearDown(): void
    {
        /*
         * Kembalikan admin.json ke kondisi sebelum test.
         */
        if ($this->originalAdminData !== null) {
            file_put_contents(
                $this->adminFile,
                $this->originalAdminData
            );
        } elseif (file_exists($this->adminFile)) {
            unlink($this->adminFile);
        }

        /*
         * Kembalikan reset_tokens.json.
         */
        if ($this->originalResetTokensData !== null) {
            file_put_contents(
                $this->resetTokensFile,
                $this->originalResetTokensData
            );
        } elseif (file_exists($this->resetTokensFile)) {
            unlink($this->resetTokensFile);
        }

        parent::tearDown();
    }

    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function testLoginWithCorrectCredentialsReturnsJwt(): void
    {
        $token = AuthService::login(
            'admin',
            'admin123@'
        );

        $this->assertIsString($token);
        $this->assertNotEmpty($token);
    }

    public function testLoginWithWrongUsernameThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Username atau password salah'
        );

        AuthService::login(
            'wronguser',
            'admin123@'
        );
    }

    public function testLoginWithWrongPasswordThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Username atau password salah'
        );

        AuthService::login(
            'admin',
            'wrongpassword'
        );
    }

    public function testFailedLoginIncrementsFailedAttempts(): void
    {
        try {
            AuthService::login(
                'admin',
                'wrongpassword'
            );
        } catch (Exception $e) {
            /*
             * Exception memang diharapkan.
             */
        }

        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $this->assertEquals(
            1,
            $admin['failedAttempts']
        );
    }

    public function testSuccessfulLoginResetsFailedAttempts(): void
    {
        /*
         * Simulasikan sebelumnya sudah gagal 3 kali.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $admin['failedAttempts'] = 3;

        file_put_contents(
            $this->adminFile,
            json_encode(
                $admin,
                JSON_PRETTY_PRINT
            )
        );

        /*
         * Login berhasil.
         */
        AuthService::login(
            'admin',
            'admin123@'
        );

        /*
         * Periksa data setelah login.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $this->assertEquals(
            0,
            $admin['failedAttempts']
        );

        $this->assertEquals(
            0,
            $admin['lockedUntil']
        );
    }

    public function testAccountIsLockedAfterFiveFailedAttempts(): void
    {
        /*
         * Lima kali login dengan password salah.
         */
        for ($i = 0; $i < 5; $i++) {
            try {
                AuthService::login(
                    'admin',
                    'wrongpassword'
                );
            } catch (Exception $e) {
                /*
                 * Exception memang diharapkan.
                 */
            }
        }

        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        /*
         * Harus tercatat 5 percobaan gagal.
         */
        $this->assertEquals(
            5,
            $admin['failedAttempts']
        );

        /*
         * lockedUntil harus lebih besar dari waktu sekarang.
         */
        $this->assertGreaterThan(
            time(),
            $admin['lockedUntil']
        );
    }

    public function testLockedAccountCannotLogin(): void
    {
        /*
         * Buat kondisi account sedang locked.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $admin['failedAttempts'] = 5;
        $admin['lockedUntil'] = time() + 180;

        file_put_contents(
            $this->adminFile,
            json_encode(
                $admin,
                JSON_PRETTY_PRINT
            )
        );

        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Terlalu banyak percobaan login.'
        );

        /*
         * Walaupun password benar,
         * login tetap harus ditolak.
         */
        AuthService::login(
            'admin',
            'admin123@'
        );
    }

    public function testExpiredLockAllowsLoginAgain(): void
    {
        /*
         * Buat kondisi lock sudah expired.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $admin['failedAttempts'] = 5;
        $admin['lockedUntil'] = time() - 10;

        file_put_contents(
            $this->adminFile,
            json_encode(
                $admin,
                JSON_PRETTY_PRINT
            )
        );

        /*
         * Login seharusnya berhasil.
         */
        $token = AuthService::login(
            'admin',
            'admin123@'
        );

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        /*
         * Lock harus di-reset.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $this->assertEquals(
            0,
            $admin['failedAttempts']
        );

        $this->assertEquals(
            0,
            $admin['lockedUntil']
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CHANGE PASSWORD
    |--------------------------------------------------------------------------
    */

    public function testChangePasswordWithWrongUsernameThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Tidak diizinkan'
        );

        AuthService::changePassword(
            'wronguser',
            'admin123@',
            'NewPassword123!'
        );
    }

    public function testChangePasswordWithWrongOldPasswordThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Password lama salah'
        );

        AuthService::changePassword(
            'admin',
            'wrongpassword',
            'NewPassword123!'
        );
    }

    public function testChangePasswordSuccessfully(): void
    {
        AuthService::changePassword(
            'admin',
            'admin123@',
            'NewPassword123!'
        );

        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        $this->assertTrue(
            password_verify(
                'NewPassword123!',
                $admin['passwordHash']
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RESET PASSWORD TOKEN
    |--------------------------------------------------------------------------
    */

    public function testGenerateResetTokenReturnsCorrectData(): void
    {
        $result = AuthService::generateResetToken();

        $this->assertIsArray($result);

        $this->assertArrayHasKey(
            'token',
            $result
        );

        $this->assertArrayHasKey(
            'email',
            $result
        );

        $this->assertEquals(
            'test@example.com',
            $result['email']
        );

        $this->assertNotEmpty(
            $result['token']
        );
    }

    public function testGeneratedResetTokenIsSaved(): void
    {
        $result = AuthService::generateResetToken();

        $tokens = json_decode(
            file_get_contents($this->resetTokensFile),
            true
        );

        $this->assertArrayHasKey(
            $result['token'],
            $tokens
        );

        $this->assertArrayHasKey(
            'expiresAt',
            $tokens[$result['token']]
        );

        /*
         * Token seharusnya berlaku sekitar 15 menit.
         */
        $this->assertGreaterThan(
            time() * 1000,
            $tokens[$result['token']]['expiresAt']
        );
    }

    public function testResetPasswordWithValidToken(): void
    {
        /*
         * Generate token.
         */
        $result = AuthService::generateResetToken();

        /*
         * Reset password menggunakan token.
         */
        AuthService::resetPassword(
            $result['token'],
            'NewPassword123!'
        );

        /*
         * Ambil admin terbaru.
         */
        $admin = json_decode(
            file_get_contents($this->adminFile),
            true
        );

        /*
         * Password harus berubah.
         */
        $this->assertTrue(
            password_verify(
                'NewPassword123!',
                $admin['passwordHash']
            )
        );
    }

    public function testResetTokenCanOnlyBeUsedOnce(): void
    {
        $result = AuthService::generateResetToken();

        /*
         * Pemakaian pertama harus berhasil.
         */
        AuthService::resetPassword(
            $result['token'],
            'NewPassword123!'
        );

        /*
         * Pemakaian kedua harus gagal.
         */
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Token reset tidak valid atau sudah kedaluwarsa'
        );

        AuthService::resetPassword(
            $result['token'],
            'AnotherPassword123!'
        );
    }

    public function testInvalidResetTokenThrowsException(): void
    {
        $this->expectException(Exception::class);

        $this->expectExceptionMessage(
            'Token reset tidak valid atau sudah kedaluwarsa'
        );

        AuthService::resetPassword(
            'invalid-token',
            'NewPassword123!'
        );
    }

    public function testExpiredResetTokenThrowsException(): void
    {
        $token = bin2hex(
            random_bytes(32)
        );

        $tokens = [
            $token => [
                'expiresAt' => (time() - 60) * 1000
            ]
        ];

        file_put_contents(
            $this->resetTokensFile,
            json_encode(
                $tokens,
                JSON_PRETTY_PRINT
            )
        );

        try {
            AuthService::resetPassword(
                $token,
                'NewPassword123!'
            );

            $this->fail(
                'Expected exception was not thrown.'
            );
        } catch (Exception $e) {
            $this->assertEquals(
                'Token reset tidak valid atau sudah kedaluwarsa',
                $e->getMessage()
            );
        }

        $tokensAfter = json_decode(
            file_get_contents($this->resetTokensFile),
            true
        );

        $this->assertArrayNotHasKey(
            $token,
            $tokensAfter
        );
    }

    public function testLoginGeneratesValidJwt(): void
    {
        $token = AuthService::login(
            'admin',
            'admin123@'
        );

        $this->assertIsString($token);
        $this->assertNotEmpty($token);

        /*
         * Token harus bisa diverifikasi.
         */
        $payload = AuthService::verifyToken(
            $token
        );

        $this->assertIsArray($payload);

        $this->assertArrayHasKey(
            'username',
            $payload
        );

        $this->assertEquals(
            'admin',
            $payload['username']
        );

        $this->assertArrayHasKey(
            'iat',
            $payload
        );

        $this->assertArrayHasKey(
            'exp',
            $payload
        );
    }

    public function testVerifyValidTokenReturnsPayload(): void
    {
        $token = AuthService::login(
            'admin',
            'admin123@'
        );

        $payload = AuthService::verifyToken(
            $token
        );

        $this->assertEquals(
            'admin',
            $payload['username']
        );

        $this->assertGreaterThan(
            0,
            $payload['iat']
        );

        $this->assertGreaterThan(
            $payload['iat'],
            $payload['exp']
        );
    }

    public function testInvalidJwtThrowsException(): void
    {
        $this->expectException(Exception::class);

        AuthService::verifyToken(
            'invalid.jwt.token'
        );
    }

    public function testTokenWithWrongSecretCannotBeVerified(): void
    {
        $token = AuthService::login(
            'admin',
            'admin123@'
        );

        $_ENV['JWT_SECRET'] = 'different-secret';

        $this->expectException(Exception::class);

        AuthService::verifyToken(
            $token
        );
    }
}
