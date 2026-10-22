<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Hki\AssetRegister\Asset\Models\Asset;

final class AssetLocation extends Model
{
    protected $table = 'asset_locations';

    protected $fillable = ['account_id', 'name', 'description', 'status'];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }
}
