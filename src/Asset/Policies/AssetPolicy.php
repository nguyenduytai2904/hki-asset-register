<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Policies;

use App\Enums\Account\AccountUserPermissionEnum;
use App\Models\User;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Rbac\AssetRegisterPermissions;

final class AssetPolicy
{
    public function accessAny(User $user, int $accountId): bool
    {
        return $this->canUseSelfService($user, $accountId)
            || $this->canViewRegistry($user, $accountId)
            || $this->canManage($user, $accountId);
    }

    public function useSelfService(User $user, int $accountId): bool
    {
        return $this->canUseSelfService($user, $accountId);
    }

    public function viewRegistry(User $user, int $accountId): bool
    {
        return $this->canViewRegistry($user, $accountId);
    }

    public function manageRegistry(User $user, int $accountId): bool
    {
        return $this->canViewRegistry($user, $accountId) && $this->canManage($user, $accountId);
    }

    public function manageCatalog(User $user, int $accountId): bool
    {
        return $this->canManage($user, $accountId);
    }

    public function viewAsset(User $user, int $assetId, int $accountId): bool
    {
        $asset = $this->findAsset($assetId, $accountId);
        if ($this->canViewRegistry($user, $accountId)) {
            return true;
        }

        return $this->canUseSelfService($user, $accountId) && $this->isKeeper($user, $asset);
    }

    public function manageAsset(User $user, int $assetId, int $accountId): bool
    {
        if (! $this->manageRegistry($user, $accountId)) {
            return false;
        }

        return $this->findAsset($assetId, $accountId)->status !== 'disposed';
    }

    private function canUseSelfService(User $user, int $accountId): bool
    {
        return $user->hasAccountPermission(AssetRegisterPermissions::SELF_USE, $accountId)
            || $user->hasAccountPermission(AccountUserPermissionEnum::VIEW, $accountId);
    }

    private function canViewRegistry(User $user, int $accountId): bool
    {
        return $user->hasAccountPermission(AssetRegisterPermissions::ASSETS_VIEW, $accountId)
            || $user->hasAccountPermission(AccountUserPermissionEnum::MANAGE, $accountId);
    }

    private function canManage(User $user, int $accountId): bool
    {
        return $user->hasAccountPermission(AssetRegisterPermissions::ASSETS_MANAGE, $accountId)
            || $user->hasAccountPermission(AccountUserPermissionEnum::MANAGE, $accountId);
    }

    private function findAsset(int $assetId, int $accountId): Asset
    {
        return Asset::query()
            ->where('account_id', $accountId)
            ->with(['handovers' => fn ($query) => $query->where('status', 'pending')->whereNull('closed_at')])
            ->findOrFail($assetId);
    }

    private function isKeeper(User $user, Asset $asset): bool
    {
        return (int) $asset->current_keeper_id === (int) $user->id
            || $asset->handovers->contains(
                fn ($handover): bool => (int) $handover->recipient_id === (int) $user->id,
            );
    }
}
