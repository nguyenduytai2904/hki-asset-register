<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Lifecycle;

use App\Models\Account;
use App\Models\User;
use App\Support\Plugin\Contracts\PluginAccountGrant;
use App\Support\Plugin\DTOs\GrantResult;

final class AssetRegisterAccountGrant implements PluginAccountGrant
{
    public function grant(Account $account, User $grantedBy, ?string $contractNote = null): GrantResult
    {
        return new GrantResult(true, '', [], '');
    }
}
