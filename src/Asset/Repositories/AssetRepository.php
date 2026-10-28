<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Repositories;

use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class AssetRepository implements AssetRepositoryInterface
{
    public function typesWithUsage(int $accountId): Collection
    {
        return AssetType::query()
            ->where('account_id', $accountId)
            ->withCount(['assets' => fn (Builder $query) => $query->where('account_id', $accountId)])
            ->orderBy('name')
            ->get();
    }

    public function locationsWithUsage(int $accountId): Collection
    {
        return AssetLocation::query()
            ->where('account_id', $accountId)
            ->withCount(['assets' => fn (Builder $query) => $query->where('account_id', $accountId)])
            ->orderBy('name')
            ->get();
    }

    public function findType(int $accountId, int $typeId): AssetType
    {
        return AssetType::query()->where('account_id', $accountId)->findOrFail($typeId);
    }

    public function findLocation(int $accountId, int $locationId): AssetLocation
    {
        return AssetLocation::query()->where('account_id', $accountId)->findOrFail($locationId);
    }

    public function createType(int $accountId, AssetTypeData $data): AssetType
    {
        return AssetType::query()->create($data->attributes + [
            'account_id' => $accountId,
            'status' => 'active',
        ]);
    }

    public function updateType(AssetType $type, AssetTypeData $data): void
    {
        $type->update($data->attributes);
    }

    public function deleteType(AssetType $type): void
    {
        $type->delete();
    }

    public function typeHasAssets(AssetType $type): bool
    {
        return $type->assets()->exists();
    }

    public function createLocation(int $accountId, AssetLocationData $data): AssetLocation
    {
        return AssetLocation::query()->create($data->attributes + [
            'account_id' => $accountId,
            'status' => 'active',
        ]);
    }

    public function updateLocation(AssetLocation $location, AssetLocationData $data): void
    {
        $location->update($data->attributes);
    }

    public function deleteLocation(AssetLocation $location): void
    {
        $location->delete();
    }

    public function locationHasAssets(AssetLocation $location): bool
    {
        return $location->assets()->exists();
    }

    public function nextCodes(int $accountId, iterable $types): array
    {
        $usedCodes = Asset::query()->where('account_id', $accountId)->pluck('code')->flip();
        $nextCodes = [];

        foreach ($types as $type) {
            $number = max(1, (int) $type->next_code_number);
            do {
                $code = $type->prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
                $number++;
            } while ($usedCodes->has($code));

            $nextCodes[$type->id] = $code;
        }
        return $nextCodes;
    }
}
