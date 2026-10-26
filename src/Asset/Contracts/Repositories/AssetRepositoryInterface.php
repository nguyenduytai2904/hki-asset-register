<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Contracts\Repositories;

use Hki\AssetRegister\Asset\DTOs\AssetData;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface AssetRepositoryInterface
{
    public function typesWithUsage(int $accountId): Collection;

    public function locationsWithUsage(int $accountId): Collection;

    public function filterTypes(int $accountId): Collection;

    public function filterLocations(int $accountId): Collection;

    public function activeTypes(int $accountId): Collection;

    public function activeLocations(int $accountId): Collection;

    public function typesForAsset(int $accountId, Asset $asset): Collection;

    public function locationsForAsset(int $accountId, Asset $asset): Collection;

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

    public function paginateRegistry(int $accountId, AssetRegistryFilters $filters): LengthAwarePaginator;

    public function findForShow(int $accountId, int $assetId): Asset;

    public function findForEdit(int $accountId, int $assetId): Asset;

    public function createWithHistory(int $accountId, int $userId, AssetData $data): Asset;

    public function updateWithHistory(int $accountId, int $userId, int $assetId, AssetData $data): Asset;

    public function activeKeeperIds(int $accountId, Collection $keeperIds): Collection;

    public function keeperIsActive(int $accountId, int $keeperId): bool;

    /**
     * @param  iterable<AssetType>  $types
     * @return array<int, string>
     */
    public function nextCodes(int $accountId, iterable $types): array;
}
