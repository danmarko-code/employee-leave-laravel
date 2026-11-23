<?php

namespace App\Support;

/**
 * Generates a one-time password that satisfies the application password policy.
 */
class TemporaryPassword
{
    private const LOWER = 'abcdefghjkmnpqrstuvwxyz';

    private const UPPER = 'ABCDEFGHJKMNPQRSTUVWXYZ';

    private const NUMBER = '23456789';

    private const SYMBOL = '!@#$%^&*()-_=+[]{};:,.?';

    public static function generate(int $length = 14): string
    {
        $length = max(12, $length);

        $groups = [self::LOWER, self::UPPER, self::NUMBER, self::SYMBOL];
        $all = implode('', $groups);
        $characters = [];

        foreach ($groups as $group) {
            $characters[] = $group[random_int(0, strlen($group) - 1)];
        }

        while (count($characters) < $length) {
            $characters[] = $all[random_int(0, strlen($all) - 1)];
        }

        for ($i = count($characters) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
        }

        return implode('', $characters);
    }
}
