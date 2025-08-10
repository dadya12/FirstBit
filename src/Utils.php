<?php
declare(strict_types=1);

class Utils
{
    public static function normalizePhone(string $phone): string
    {
        $p = trim($phone);
        $p = preg_replace('/[^\d\+]/', '', $p);
        return $p;
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
