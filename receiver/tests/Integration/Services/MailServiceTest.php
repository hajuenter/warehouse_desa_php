<?php

namespace Tests\Integration\Services;

use App\Services\MailService;
use Exception;
use PHPUnit\Framework\TestCase;

class MailServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /*
         * Gunakan 127.0.0.1 dengan port yang tidak listening
         * agar SMTP connection langsung "Connection refused"
         * dan test selesai cepat.
         */
        $_ENV['SMTP_HOST'] = '127.0.0.1';
        $_ENV['SMTP_PORT'] = '19999';
        $_ENV['SMTP_USER'] = 'test@example.com';
        $_ENV['SMTP_PASS'] = 'wrong-password';
        $_ENV['FRONTEND_URL'] = 'http://localhost:3000';
    }

    /*----------------------------------------------------------------------+
    |  sendResetPasswordEmail()                                             |
    +----------------------------------------------------------------------*/

    public function testSendResetPasswordEmailWithInvalidSmtpThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Gagal mengirim email:');

        MailService::sendResetPasswordEmail(
            'recipient@example.com',
            'test-reset-token-123'
        );
    }

    public function testSendResetPasswordEmailWithInvalidEmailFormatThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Gagal mengirim email:');

        MailService::sendResetPasswordEmail(
            'not-an-email',
            'test-reset-token-123'
        );
    }

    public function testSendResetPasswordEmailUsesCorrectMethod(): void
    {
        try {
            MailService::sendResetPasswordEmail(
                'recipient@example.com',
                'test-token'
            );
            $this->fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            $this->assertStringContainsString(
                'Gagal mengirim email:',
                $e->getMessage()
            );
        }
    }

    public function testSendResetPasswordEmailWithEmptyTokenThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Gagal mengirim email:');

        MailService::sendResetPasswordEmail(
            'recipient@example.com',
            ''
        );
    }
}
