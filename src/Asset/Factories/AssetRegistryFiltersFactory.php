<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Factories;

use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;

final class AssetRegistryFiltersFactory
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): AssetRegistryFilters
    {
        return new AssetRegistryFilters(
            search: trim((string) ($data['q'] ?? '')),
            statuses: array_values($data['status'] ?? []),
            typeIds: array_map('intval', $data['type'] ?? []),
            locationIds: array_map('intval', $data['location'] ?? []),
            warranties: array_values($data['warranty'] ?? []),
            sort: (string) ($data['sort'] ?? 'asset'),
            direction: (string) ($data['direction'] ?? 'asc'),
            perPage: (int) ($data['per_page'] ?? 15),
        );
    }
}
