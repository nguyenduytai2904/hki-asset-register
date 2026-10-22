<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\DTOs;

final readonly class AssetTypeData
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(public array $attributes) {}
}
