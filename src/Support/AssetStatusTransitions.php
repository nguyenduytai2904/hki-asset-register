<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Support;

final class AssetStatusTransitions
{
    private const ALLOWED = [
        'available' => ['in_repair', 'broken', 'lost'],
        'in_use' => ['in_repair', 'broken', 'lost'],
        'in_repair' => ['available', 'broken'],
        'broken' => ['available', 'in_repair'],
        'lost' => ['available'],
        'disposed' => [],
    ];

    /** @return list<string> */
    public static function allowedFrom(string $status): array
    {
        return self::ALLOWED[$status] ?? [];
    }

    public static function allows(string $from, string $to): bool
    {
        return in_array($to, self::allowedFrom($from), true);
    }
}
