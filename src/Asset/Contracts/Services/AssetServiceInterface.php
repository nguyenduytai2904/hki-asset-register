<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Contracts\Services;

use App\Models\User;
use Hki\AssetRegister\Asset\DTOs\AssetData;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;

interface AssetServiceInterface
{
    public function landingRoute(User $user): string;

    /**
     * @return array<string, mixed>
     */
    public function minePage(User $user): array;

    /**
     * @return array<string, mixed>
     */
    public function settingsPage(User $user): array;

    public function createType(User $user, AssetTypeData $data): AssetType;

    public function updateType(User $user, int $typeId, AssetTypeData $data): void;

    public function deleteType(User $user, int $typeId): void;

    public function createLocation(User $user, AssetLocationData $data): AssetLocation;

    public function updateLocation(User $user, int $locationId, AssetLocationData $data): void;

    public function deleteLocation(User $user, int $locationId): void;

    /**
     * @return array<string, mixed>
     */
    public function registryPage(User $user, AssetRegistryFilters $filters): array;

    /**
     * @return array<string, mixed>
     */
    public function createForm(User $user): array;

    public function create(User $user, AssetData $data): Asset;

    /**
     * @return array<string, mixed>
     */
    public function detailPage(User $user, int $assetId): array;

    /**
     * @return array<string, mixed>
     */
    public function editForm(User $user, int $assetId): array;

    public function update(User $user, int $assetId, AssetData $data): Asset;
}
