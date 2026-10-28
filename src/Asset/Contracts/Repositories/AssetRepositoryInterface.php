<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Contracts\Repositories;

use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Support\Collection;

interface AssetRepositoryInterface
{
    public function typesWithUsage(int $accountId): Collection;

    public function locationsWithUsage(int $accountId): Collection;

    public function findType(int $accountId, int $typeId): AssetType;

    public function findLocation(int $accountId, int $locationId): AssetLocation;

    public function createType(int $accountId, AssetTypeData $data): AssetType;

    public function updateType(AssetType $type, AssetTypeData $data): void;

    public function deleteType(AssetType $type): void;

    public function typeHasAssets(AssetType $type): bool;

    public function createLocation(int $accountId, AssetLocationData $data): AssetLocation;

    public function updateLocation(AssetLocation $location, AssetLocationData $data): void;

    public function deleteLocation(AssetLocation $location): void;

    public function locationHasAssets(AssetLocation $location): bool;

    /**
     * @param  iterable<AssetType>  $types
     * @return array<int, string>
     */
    public function nextCodes(int $accountId, iterable $types): array;
}
