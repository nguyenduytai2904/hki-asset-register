<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Factories;

use Hki\AssetRegister\Asset\DTOs\AssetLocationData;

final class AssetLocationDataFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): AssetLocationData
    {
        return new AssetLocationData($data);
    }
}
