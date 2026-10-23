<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Factories;

use Hki\AssetRegister\Asset\DTOs\AssetData;

final class AssetDataFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): AssetData
    {
        return new AssetData($data);
    }
}
