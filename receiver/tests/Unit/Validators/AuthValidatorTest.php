<?php

namespace Tests\Unit\Validators;

use App\Validators\AuthValidator;
use PHPUnit\Framework\TestCase;
use Exception;

class AuthValidatorTest extends TestCase
{
    public function testValidPasswordDoesNotThrowException(): void
    {
        AuthValidator::validatePassword('Password1!');
        $this->assertTrue(true);
    }

    public function testEmptyPasswordThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password tidak boleh kosong.');

        AuthValidator::validatePassword('');
    }

    public function testWhitespaceOnlyPasswordThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password tidak boleh kosong.');

        AuthValidator::validatePassword('     ');
    }

    public function testShortPasswordThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password minimal harus terdiri dari 8 karakter.');

        AuthValidator::validatePassword('Ab1!');
    }

    public function testPasswordWithoutUppercaseThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password harus mengandung minimal satu huruf kapital.');

        AuthValidator::validatePassword('password1!');
    }

    public function testPasswordWithoutLowercaseThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password harus mengandung minimal satu huruf kecil.');

        AuthValidator::validatePassword('PASSWORD1!');
    }

    public function testPasswordWithoutDigitThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password harus mengandung minimal satu angka.');

        AuthValidator::validatePassword('Password!');
    }

    public function testPasswordWithoutSpecialCharacterThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Password harus mengandung minimal satu karakter khusus.');

        AuthValidator::validatePassword('Password1');
    }
}
