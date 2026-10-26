<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\DTOs;

final readonly class AssetLocationData
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(public array $attributes) {}
}
