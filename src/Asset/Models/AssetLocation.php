<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use App\Support\Account\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AssetLocation extends Model
{
    use BelongsToAccount;

    protected $table = 'asset_register_locations';

    protected $fillable = ['account_id', 'name', 'description', 'status'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
