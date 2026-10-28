<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Unit;

use Carbon\CarbonImmutable;
use DateTimeZone;
use Hki\AssetRegister\Support\AssetWarranty;
use PHPUnit\Framework\TestCase;

final class AssetWarrantyTest extends TestCase
{
    public function test_days_remaining_uses_calendar_dates_across_timezone_offsets(): void
    {
        $today = CarbonImmutable::parse('2026-10-24 00:30:00', 'Pacific/Kiritimati');
        $endDate = CarbonImmutable::parse('2026-11-10 23:45:00', 'America/Los_Angeles');

        self::assertSame(17, AssetWarranty::daysUntil($endDate, $today));
    }

    public function test_days_remaining_is_not_fractional_across_daylight_saving_changes(): void
    {
        $timezone = new DateTimeZone('America/New_York');
        $today = CarbonImmutable::parse('2026-03-08 00:00:00', $timezone);
        $endDate = CarbonImmutable::parse('2026-03-09 00:00:00', $timezone);

        self::assertSame(1, AssetWarranty::daysUntil($endDate, $today));
    }

    public function test_expiry_date_uses_calendar_months_without_overflow(): void
    {
        self::assertSame('2025-02-28', AssetWarranty::endDateFromDuration('2025-01-31', 1));
        self::assertSame('2025-02-28', AssetWarranty::endDateFromDuration('2024-02-29', 12));
        self::assertSame('2025-04-30', AssetWarranty::endDateFromDuration('2025-01-31', 3));
    }
}
