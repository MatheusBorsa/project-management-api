<?php

namespace App\Utils;

class PhoneUtil
{
    public static function digits(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    public static function isValid(string $phone): bool
    {
        $digits = self::digits($phone);
        return preg_match('/^\d{10,11}$/', $digits);
    }
    
    public static function format(?string $phone): ?string
    {
        if (!$phone) return null;

        $digits = self::digits($phone);

        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 5),
                substr($digits, 7)
            );
        }

        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s',
                substr($digits, 0, 2),
                substr($digits, 2, 4),
                substr($digits, 6)
            );
        }

        return $digits;
    }
}
