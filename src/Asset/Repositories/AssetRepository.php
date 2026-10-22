<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Repositories;

use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\DTOs\AssetData;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetHistory;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    public function filterTypes(int $accountId): Collection
    {
        return AssetType::query()->where('account_id', $accountId)->orderBy('name')->get();
    }

    public function filterLocations(int $accountId): Collection
    {
        return AssetLocation::query()->where('account_id', $accountId)->orderBy('name')->get();
    }

    public function activeTypes(int $accountId): Collection
    {
        return AssetType::query()->where('account_id', $accountId)->where('status', 'active')->orderBy('name')->get();
    }

    public function activeLocations(int $accountId): Collection
    {
        return AssetLocation::query()->where('account_id', $accountId)->where('status', 'active')->orderBy('name')->get();
    }

    public function typesForAsset(int $accountId, Asset $asset): Collection
    {
        return AssetType::query()
            ->where('account_id', $accountId)
            ->where(fn (Builder $query) => $query->where('status', 'active')->orWhere('id', $asset->asset_type_id))
            ->orderBy('name')
            ->get();
    }

    public function locationsForAsset(int $accountId, Asset $asset): Collection
    {
        return AssetLocation::query()
            ->where('account_id', $accountId)
            ->where(fn (Builder $query) => $query->where('status', 'active')->orWhere('id', $asset->asset_location_id))
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

    public function paginateRegistry(int $accountId, AssetRegistryFilters $filters): LengthAwarePaginator
    {
        $query = Asset::query()
            ->where('assets.account_id', $accountId)
            ->with([
                'type' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
                'location' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
                'keeper',
            ])
            ->withExists([
                'handovers as has_pending_handover' => fn (Builder $query) => $query
                    ->where('status', 'pending')
                    ->whereNull('closed_at'),
            ]);

        if ($filters->search !== '') {
            $search = $filters->search;
            $query->where(function (Builder $query) use ($search): void {
                $query->where('assets.code', 'like', '%'.$search.'%')
                    ->orWhere('assets.name', 'like', '%'.$search.'%')
                    ->orWhere('assets.brand', 'like', '%'.$search.'%')
                    ->orWhere('assets.model', 'like', '%'.$search.'%')
                    ->orWhere('assets.serial', 'like', '%'.$search.'%')
                    ->orWhereHas('keeper', fn (Builder $keeper) => $keeper->where('name', 'like', '%'.$search.'%'));
            });
        }

        $this->applyStatusFilter($query, $filters->statuses);
        if ($filters->typeIds !== []) {
            $query->whereIn('assets.asset_type_id', $filters->typeIds);
        }
        if ($filters->locationIds !== []) {
            $query->whereIn('assets.asset_location_id', $filters->locationIds);
        }
        $this->applyWarrantyFilter($query, $filters->warranties);
        $this->applySorting($query, $filters->sort, $filters->direction);

        return $query->paginate($filters->perPage)->withQueryString();
    }

    public function findForShow(int $accountId, int $assetId): Asset
    {
        return Asset::query()
            ->where('account_id', $accountId)
            ->with([
                'type' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
                'location' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
                'keeper',
                'handovers' => fn (HasMany $query) => $query->with('recipient')->latest('handed_over_at'),
                'histories' => fn (HasMany $query) => $query->with('user')->latest('created_at'),
            ])
            ->findOrFail($assetId);
    }

    public function findForEdit(int $accountId, int $assetId): Asset
    {
        return Asset::query()
            ->where('account_id', $accountId)
            ->with([
                'type' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
                'location' => fn (BelongsTo $query) => $query->where('account_id', $accountId),
            ])
            ->findOrFail($assetId);
    }

    public function createWithHistory(int $accountId, int $userId, AssetData $data): Asset
    {
        return DB::transaction(function () use ($accountId, $userId, $data): Asset {
            $attributes = $data->attributes;
            $type = AssetType::query()
                ->where('account_id', $accountId)
                ->where('status', 'active')
                ->lockForUpdate()
                ->findOrFail((int) $attributes['asset_type_id']);

            $manualCode = $attributes['code'] ?? null;
            $code = $manualCode ?: $this->allocateCode($type, $accountId);
            if ($manualCode === null || $manualCode === '') {
                $type->next_code_number = $this->codeNumber($code) + 1;
                $type->save();
            }

            $asset = Asset::query()->create([
                ...$attributes,
                'account_id' => $accountId,
                'code' => $code,
                'status' => 'available',
                'current_keeper_id' => null,
            ]);

            AssetHistory::query()->create([
                'asset_id' => $asset->id,
                'user_id' => $userId,
                'action' => 'added',
                'description' => json_encode(['code' => $code], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            ]);

            return $asset;
        }, 3);
    }

    public function updateWithHistory(int $accountId, int $userId, int $assetId, AssetData $data): Asset
    {
        return DB::transaction(function () use ($accountId, $userId, $assetId, $data): Asset {
            $asset = $this->findForEdit($accountId, $assetId);
            $changes = [];

            foreach ($data->attributes as $field => $value) {
                $oldValue = $asset->getRawOriginal($field);
                if ((string) ($oldValue ?? '') === (string) ($value ?? '')) {
                    continue;
                }

                if ($field === 'asset_type_id') {
                    $oldValue = $asset->type?->name;
                    $value = AssetType::query()->where('account_id', $accountId)->findOrFail((int) $value)->name;
                } elseif ($field === 'asset_location_id') {
                    $oldValue = $asset->location?->name;
                    $value = $value === null
                        ? null
                        : DB::table('asset_locations')
                            ->where('account_id', $accountId)
                            ->where('id', (int) $value)
                            ->value('name');
                }

                $changes[$field] = ['old' => $oldValue, 'new' => $value];
            }

            $asset->update($data->attributes);
            if ($changes !== []) {
                AssetHistory::query()->create([
                    'asset_id' => $asset->id,
                    'user_id' => $userId,
                    'action' => 'updated',
                    'description' => json_encode($changes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                ]);
            }

            return $asset;
        });
    }

    public function activeKeeperIds(int $accountId, Collection $keeperIds): Collection
    {
        if ($keeperIds->isEmpty()) {
            return collect();
        }

        return DB::table('account_user')
            ->join('users', 'users.id', '=', 'account_user.user_id')
            ->where('account_user.account_id', $accountId)
            ->whereIn('account_user.user_id', $keeperIds)
            ->whereNull('account_user.suspended_at')
            ->whereNull('users.deleted_at')
            ->whereNull('users.banned_at')
            ->pluck('account_user.user_id');
    }

    public function keeperIsActive(int $accountId, int $keeperId): bool
    {
        return DB::table('account_user')
            ->join('users', 'users.id', '=', 'account_user.user_id')
            ->where('account_user.account_id', $accountId)
            ->where('account_user.user_id', $keeperId)
            ->whereNull('account_user.suspended_at')
            ->whereNull('users.deleted_at')
            ->whereNull('users.banned_at')
            ->exists();
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

    private function applyStatusFilter(Builder $query, array $statuses): void
    {
        if ($statuses === []) {
            return;
        }

        $regularStatuses = array_values(array_diff($statuses, ['pending']));
        $query->where(function (Builder $statusQuery) use ($statuses, $regularStatuses): void {
            if ($regularStatuses !== []) {
                $statusQuery->whereIn('assets.status', $regularStatuses);
            }

            if (in_array('pending', $statuses, true)) {
                $pendingHandover = fn (Builder $handover) => $handover
                    ->where('status', 'pending')
                    ->whereNull('closed_at');

                if ($regularStatuses === []) {
                    $statusQuery->whereHas('handovers', $pendingHandover);
                } else {
                    $statusQuery->orWhereHas('handovers', $pendingHandover);
                }
            }
        });
    }

    private function applyWarrantyFilter(Builder $query, array $warranties): void
    {
        if ($warranties === []) {
            return;
        }

        $query->where(function (Builder $warrantyQuery) use ($warranties): void {
            foreach ($warranties as $index => $warranty) {
                $applyWarranty = static function (Builder $query) use ($warranty): void {
                    if ($warranty === 'expired') {
                        $query->whereNotNull('assets.warranty_end_date')
                            ->whereDate('assets.warranty_end_date', '<', today());
                    } else {
                        $query->whereDate('assets.warranty_end_date', '>=', today())
                            ->whereDate('assets.warranty_end_date', '<=', today()->addDays(30));
                    }
                };

                if ($index === 0) {
                    $applyWarranty($warrantyQuery);
                } else {
                    $warrantyQuery->orWhere($applyWarranty);
                }
            }
        });
    }

    private function applySorting(Builder $query, string $sort, string $direction): void
    {
        if ($sort === 'status') {
            $query->orderByRaw("CASE assets.status
                WHEN 'available' THEN 1
                WHEN 'in_use' THEN 2
                WHEN 'in_repair' THEN 3
                WHEN 'broken' THEN 4
                WHEN 'lost' THEN 5
                WHEN 'disposed' THEN 6
                ELSE 7 END {$direction}")
                ->orderBy('assets.code');
        } elseif ($sort === 'warranty') {
            $query->orderByRaw('assets.warranty_end_date IS NULL')
                ->orderBy('assets.warranty_end_date', $direction)
                ->orderBy('assets.code');
        } else {
            $query->orderBy('assets.code', $direction);
        }
    }

    private function allocateCode(AssetType $type, int $accountId): string
    {
        $number = max(1, (int) $type->next_code_number);
        do {
            $code = $type->prefix.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
            $number++;
        } while (Asset::query()->where('account_id', $accountId)->where('code', $code)->exists());

        return $code;
    }

    private function codeNumber(string $code): int
    {
        return (int) substr($code, strrpos($code, '-') + 1);
    }
}
