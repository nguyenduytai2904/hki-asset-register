<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Services;

use App\Models\User;
use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Contracts\Repositories\AssetRepositoryInterface;
use Hki\AssetRegister\Asset\Contracts\Services\AssetServiceInterface;
use Hki\AssetRegister\Asset\DTOs\AssetLocationData;
use Hki\AssetRegister\Asset\DTOs\AssetTypeData;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetLocation;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class AssetService implements AssetServiceInterface
{
    public function __construct(private readonly AssetRepositoryInterface $assets) {}

    public function landingRoute(User $user): string
    {
        $accountId = AccountContext::getOrFail();
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
