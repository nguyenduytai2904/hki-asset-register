<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Factories;

use Hki\AssetRegister\Asset\DTOs\AssetData;
use Hki\AssetRegister\Support\AssetWarranty;

final class AssetDataFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): AssetData
    {
        unset($data['warranty_duration_preset'], $data['warranty_duration_custom_months']);

        if (
            empty($data['warranty_end_date'])
            && ! empty($data['purchase_date'])
            && ! empty($data['warranty_duration_months'])
        ) {
            $data['warranty_end_date'] = AssetWarranty::endDateFromDuration(
                (string) $data['purchase_date'],
                (int) $data['warranty_duration_months'],
            );
        }

        return new AssetData($data);
    }
}
