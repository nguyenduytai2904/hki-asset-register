<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Models\Account;
use App\Models\User;
use App\Support\Plugin\Contracts\PluginAccountRevoke;
use App\Support\Plugin\DTOs\RevokeResult;

final class AssetRegisterAccountRevoke implements PluginAccountRevoke
{
    public function revoke(Account $account, User $revokedBy, ?string $reason = null): RevokeResult
    {
        return new RevokeResult(true, [], '');
    }
}
