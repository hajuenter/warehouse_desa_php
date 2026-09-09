<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailService
{
    public static function sendResetPasswordEmail(string $toEmail, string $token): void
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth = true;
            $mail->Username = $_ENV['SMTP_USER'];
            $mail->Password = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$_ENV['SMTP_PORT'];

            $mail->setFrom($_ENV['SMTP_USER'], 'Monitoring Sinkronisasi Desa');
            $mail->addAddress($toEmail);

            $resetUrl = $_ENV['FRONTEND_URL'] . '/reset-password?token=' . $token;

            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Dashboard Monitoring';
            $mail->Body = "
                <p>Ada permintaan reset password untuk dashboard monitoring.</p>
                <p><a href=\"{$resetUrl}\">Klik di sini untuk atur password baru</a></p>
                <p>Tautan ini berlaku 15 menit. Kalau kamu tidak meminta ini, abaikan email ini.</p>
            ";

            $mail->send();
        } catch (PHPMailerException $e) {
            throw new \Exception('Gagal mengirim email: ' . $mail->ErrorInfo);
        }
    }
}
