<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use App\Models\User;
use App\Support\Account\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AssetHistory extends Model
{
    use BelongsToAccount;

    protected $table = 'asset_register_histories';

    protected $fillable = ['account_id', 'asset_id', 'user_id', 'action', 'description'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
