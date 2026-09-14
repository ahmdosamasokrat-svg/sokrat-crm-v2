<?php

declare(strict_types=1);

namespace App\Support;

class PhoneMask
{
    public static function mask(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $clean = trim($phone);
        if (mb_strlen($clean) < 7) {
            return $clean;
        }

        $isPlus = str_starts_with($clean, '+');
        $pfx = $isPlus ? 5 : 4;
        $sfx = 3;

        if (mb_strlen($clean) <= ($pfx + $sfx)) {
            return mb_substr($clean, 0, 3) . '****' . mb_substr($clean, -2);
        }

        return mb_substr($clean, 0, $pfx) . '****' . mb_substr($clean, -$sfx);
    }
}
