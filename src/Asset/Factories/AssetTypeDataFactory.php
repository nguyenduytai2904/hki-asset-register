<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Factories;

use Hki\AssetRegister\Asset\DTOs\AssetTypeData;

final class AssetTypeDataFactory
{
    /**
     * @param array<string, mixed> $data
     */
    public function fromArray(array $data): AssetTypeData
    {
        return new AssetTypeData($data);
    }
}
