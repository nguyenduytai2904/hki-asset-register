<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Tests\Unit;

use Hki\AssetRegister\Support\AssetStatusTransitions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AssetStatusTransitionsTest extends TestCase
{
    #[DataProvider('transitions')]
    public function test_only_business_allowed_status_transitions_are_available(string $from, array $expected): void
    {
        self::assertSame($expected, AssetStatusTransitions::allowedFrom($from));
    }

    public static function transitions(): array
    {
        return [
            'available' => ['available', ['in_repair', 'broken', 'lost']],
            'in use' => ['in_use', ['in_repair', 'broken', 'lost']],
            'in repair' => ['in_repair', ['available', 'broken']],
            'broken' => ['broken', ['available', 'in_repair']],
            'lost' => ['lost', ['available']],
            'disposed' => ['disposed', []],
            'unknown' => ['unknown', []],
        ];
    }
}
