<?php

namespace App\Services;

/** Egyptian mobile numbers, stored as 01XXXXXXXXX. */
class Phone
{
    public const PATTERN = '/^01[0125][0-9]{8}$/';

    public static function normalize(?string $input): string
    {
        $digits = strtr((string) $input, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $digits = preg_replace('/\D+/', '', $digits);

        if (str_starts_with($digits, '0020')) {
            $digits = substr($digits, 4);
        } elseif (str_starts_with($digits, '20') && strlen($digits) === 12) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) === 10 && str_starts_with($digits, '1')) {
            $digits = '0'.$digits;
        }

        return $digits;
    }

    public static function valid(string $phone): bool
    {
        return (bool) preg_match(self::PATTERN, $phone);
    }

    public static function mask(string $phone): string
    {
        return substr($phone, 0, 3).' ••• '.substr($phone, -3);
    }
}
