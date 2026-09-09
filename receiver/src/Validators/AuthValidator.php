<?php

namespace App\Validators;

use Exception;

class AuthValidator
{
    public static function validatePassword(string $password): void
    {
        if (trim($password) === '') {
            throw new Exception("Password tidak boleh kosong.");
        }

        if (strlen($password) < 8) {
            throw new Exception(
                "Password minimal harus terdiri dari 8 karakter."
            );
        }

        if (!preg_match('/[A-Z]/', $password)) {
            throw new Exception(
                "Password harus mengandung minimal satu huruf kapital."
            );
        }

        if (!preg_match('/[a-z]/', $password)) {
            throw new Exception(
                "Password harus mengandung minimal satu huruf kecil."
            );
        }

        if (!preg_match('/[0-9]/', $password)) {
            throw new Exception(
                "Password harus mengandung minimal satu angka."
            );
        }

        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            throw new Exception(
                "Password harus mengandung minimal satu karakter khusus."
            );
        }
    }
}
