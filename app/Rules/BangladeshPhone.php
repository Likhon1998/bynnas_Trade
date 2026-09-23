<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class BangladeshPhone implements ValidationRule
{
    public function __construct(private bool $required = true) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || trim((string) $value) === '') {
            if ($this->required) {
                $fail('Phone number is required.');
            }

            return;
        }

        $digits = preg_replace('/\D+/', '', (string) $value) ?: '';

        if (strlen($digits) < 11) {
            $fail('Phone must have at least 11 digits (Bangladesh mobile, e.g. 01712-345678).');

            return;
        }

        if (strlen($digits) > 13 && ! str_starts_with($digits, '880')) {
            $fail('Phone looks too long. Use an 11-digit Bangladesh mobile (01XXXXXXXXX).');

            return;
        }

        // Normalize 8801XXXXXXXXX → 01XXXXXXXXX for format check
        $local = $digits;
        if (str_starts_with($local, '880') && strlen($local) >= 13) {
            $local = '0'.substr($local, 3);
        }

        // Valid BD mobiles: 013–019 + 8 more digits = 11 total
        if (! preg_match('/^01[3-9]\d{8}$/', $local)) {
            $fail('Enter a valid Bangladesh mobile number (11 digits, starting with 013–019).');
        }
    }

    /**
     * Store a clean local form when possible: 01XXXXXXXXX
     */
    public static function normalize(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if (str_starts_with($digits, '880') && strlen($digits) >= 13) {
            return '0'.substr($digits, 3, 10);
        }

        return $digits;
    }
}
