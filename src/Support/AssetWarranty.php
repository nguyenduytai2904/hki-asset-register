<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

final class AssetWarranty
{
    public static function endDateFromDuration(string $purchaseDate, int $durationMonths): string
    {
        return CarbonImmutable::parse($purchaseDate, 'UTC')
            ->addMonthsNoOverflow($durationMonths)
            ->format('Y-m-d');
    }

    public static function daysUntil(DateTimeInterface $endDate, DateTimeInterface $today): int
    {
        $end = CarbonImmutable::parse($endDate->format('Y-m-d'), 'UTC');
        $start = CarbonImmutable::parse($today->format('Y-m-d'), 'UTC');

        return (int) $start->diffInDays($end);
    }
}
