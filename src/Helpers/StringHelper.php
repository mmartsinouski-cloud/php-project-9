<?php

declare(strict_types=1);

namespace App\Helpers;

class StringHelper
{
    /**
     * @param string|null $string
     * @param int $length
     * @return string|null
     */
    public static function truncate(?string $string, int $length): ?string
    {
        if ($string === null) {
            return null;
        }

        $string = trim($string);

        if (mb_strlen($string) <= $length) {
            return $string;
        }

        return mb_substr($string, 0, $length) . '...';
    }
}