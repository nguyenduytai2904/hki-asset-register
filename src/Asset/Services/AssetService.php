<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Services;

use App\Models\User;
use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\DTOs\AssetData;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetRegistryFilters;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Hki\AssetRegister\Support\AssetRegisterPreviewData;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssetService implements AssetServiceInterface
{
    public function __construct(private readonly AssetRepositoryInterface $assets) {}

    public function landingRoute(User $user): string
    {
        $accountId = AccountContext::getOrFail();
        if ($user->can('viewRegistry', [Asset::class, $accountId])) {
            return 'asset-register.registry';
        }
        if ($user->can('useSelfService', [Asset::class, $accountId])) {
            return 'asset-register.mine';
        }
        if ($user->can('accessSettings', [Asset::class, $accountId])) {
            return 'asset-register.settings';
        }

        abort(403);
    }

    public function minePage(User $user): array
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('useSelfService', [Asset::class, $accountId]);

        return [
            'activeTab' => 'mine',
            'tabs' => $this->tabs($user, $accountId),
            'preview' => AssetRegisterPreviewData::mine(),
        ];
    }

    public function settingsPage(User $user): array
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('accessSettings', [Asset::class, $accountId]);

        $types = $this->assets->typesWithUsage($accountId);
        $nextCodes = $this->assets->nextCodes($accountId, $types);
        foreach ($types as $type) {
            $type->setAttribute('next_code', $nextCodes[$type->id]);
        }

        return [
            'activeTab' => 'settings',
            'tabs' => $this->tabs($user, $accountId),
            'types' => $types,
            'locations' => $this->assets->locationsWithUsage($accountId),
            'canManageCatalog' => $user->can('manageCatalog', [Asset::class, $accountId]),
        ];
    }

    public function createType(User $user, AssetTypeData $data): AssetType
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);

        return $this->assets->createType($accountId, $data);
    }

    public function updateType(User $user, int $typeId, AssetTypeData $data): void
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);
        $type = $this->assets->findType($accountId, $typeId);

        if ($this->assets->typeHasAssets($type) && $data->attributes['prefix'] !== $type->prefix) {
            throw ValidationException::withMessages([
                'prefix' => __('asset-register::asset-register.validation.prefix_locked'),
            ]);
        }

        $this->assets->updateType($type, $data);
    }

    public function deleteType(User $user, int $typeId): void
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);
        $type = $this->assets->findType($accountId, $typeId);
        if ($this->assets->typeHasAssets($type)) {
            throw ValidationException::withMessages([
                'type' => __('asset-register::asset-register.validation.type_in_use'),
            ]);
        }

        $this->assets->deleteType($type);
    }

    public function createLocation(User $user, AssetLocationData $data): AssetLocation
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);

        return $this->assets->createLocation($accountId, $data);
    }

    public function updateLocation(User $user, int $locationId, AssetLocationData $data): void
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);
        $location = $this->assets->findLocation($accountId, $locationId);
        $this->assets->updateLocation($location, $data);
    }

    public function deleteLocation(User $user, int $locationId): void
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageCatalog', [Asset::class, $accountId]);
        $location = $this->assets->findLocation($accountId, $locationId);
        if ($this->assets->locationHasAssets($location)) {
            throw ValidationException::withMessages([
                'location' => __('asset-register::asset-register.validation.location_in_use'),
            ]);
        }

        $this->assets->deleteLocation($location);
    }

    public function registryPage(User $user, AssetRegistryFilters $filters): array
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('viewRegistry', [Asset::class, $accountId]);
        $assets = $this->assets->paginateRegistry($accountId, $filters);
        $keeperIds = $assets->getCollection()->pluck('current_keeper_id')->filter()->unique()->values();
        $activeKeeperIds = $this->assets->activeKeeperIds($accountId, $keeperIds);

        $assets->getCollection()->each(function (Asset $asset) use ($activeKeeperIds): void {
            $asset->setAttribute(
                'keeper_is_inactive',
                $asset->current_keeper_id !== null && ! $activeKeeperIds->contains($asset->current_keeper_id),
            );
        });

        return [
            'activeTab' => 'registry',
            'tabs' => $this->tabs($user, $accountId),
            'assets' => $assets,
            'filters' => $filters->toArray(),
            'types' => $this->assets->filterTypes($accountId),
            'locations' => $this->assets->filterLocations($accountId),
            'canManage' => $user->can('manageCatalog', [Asset::class, $accountId]),
        ];
    }

    public function createForm(User $user): array
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageRegistry', [Asset::class, $accountId]);
        $types = $this->assets->activeTypes($accountId);

        return [
            'activeTab' => 'registry',
            'tabs' => $this->tabs($user, $accountId),
            'asset' => null,
            'types' => $types,
            'locations' => $this->assets->activeLocations($accountId),
            'nextCodes' => $this->assets->nextCodes($accountId, $types),
        ];
    }

    public function create(User $user, AssetData $data): Asset
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageRegistry', [Asset::class, $accountId]);

        return $this->assets->createWithHistory($accountId, (int) $user->id, $data);
    }

    public function detailPage(User $user, int $assetId): array
    {
        $accountId = AccountContext::getOrFail();
        $asset = $this->assets->findForShow($accountId, $assetId);
        Gate::authorize('viewAsset', [Asset::class, $assetId, $accountId]);
        $canViewRegistry = $user->can('viewRegistry', [Asset::class, $accountId]);

        if (! $canViewRegistry) {
            $asset->setRelation('handovers', $asset->handovers->where('recipient_id', $user->id)->values());
            $asset->setRelation('histories', $asset->histories->where('user_id', $user->id)->values());
        }

        $activeKeeper = $asset->current_keeper_id === null
            ? false
            : $this->assets->keeperIsActive($accountId, (int) $asset->current_keeper_id);
        $asset->setAttribute('keeper_is_inactive', $asset->current_keeper_id !== null && ! $activeKeeper);

        return [
            'activeTab' => $canViewRegistry ? 'registry' : 'mine',
            'tabs' => $this->tabs($user, $accountId),
            'asset' => $asset,
            'isKeeperView' => ! $canViewRegistry,
            'canManage' => $user->can('manageCatalog', [Asset::class, $accountId]) && $asset->status !== 'disposed',
        ];
    }

    public function editForm(User $user, int $assetId): array
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageAsset', [Asset::class, $assetId, $accountId]);
        $asset = $this->assets->findForEdit($accountId, $assetId);

        return [
            'activeTab' => 'registry',
            'tabs' => $this->tabs($user, $accountId),
            'asset' => $asset,
            'types' => $this->assets->typesForAsset($accountId, $asset),
            'locations' => $this->assets->locationsForAsset($accountId, $asset),
            'nextCodes' => [],
        ];
    }

    public function update(User $user, int $assetId, AssetData $data): Asset
    {
        $accountId = AccountContext::getOrFail();
        Gate::authorize('manageAsset', [Asset::class, $assetId, $accountId]);

        return $this->assets->updateWithHistory($accountId, (int) $user->id, $assetId, $data);
    }

    /**
     * @return list<array{key: string, label: string, icon: string, url: string}>
     */
    private function tabs(User $user, int $accountId): array
    {
        $tabs = [];
        if ($user->can('useSelfService', [Asset::class, $accountId])) {
            $tabs[] = [
                'key' => 'mine',
                'label' => __('asset-register::asset-register.tabs.mine'),
                'icon' => 'person',
                'url' => route('asset-register.mine'),
            ];
        }
        if ($user->can('viewRegistry', [Asset::class, $accountId])) {
            $tabs[] = [
                'key' => 'registry',
                'label' => __('asset-register::asset-register.tabs.registry'),
                'icon' => 'inventory_2',
                'url' => route('asset-register.registry'),
            ];
        }
        if ($user->can('accessSettings', [Asset::class, $accountId])) {
            $tabs[] = [
                'key' => 'settings',
                'label' => __('asset-register::asset-register.tabs.settings'),
                'icon' => 'settings',
                'url' => route('asset-register.settings'),
            ];
        }

        return $tabs;
    }
}
