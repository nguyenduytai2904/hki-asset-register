<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use App\Models\User;
use App\Support\Account\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AssetHandover extends Model
{
    use BelongsToAccount;

    protected $table = 'asset_register_handovers';

    protected $fillable = [
        'account_id', 'asset_id', 'recipient_id', 'status', 'condition_on_handover',
        'condition_on_return', 'note', 'handed_over_at', 'confirmed_at', 'closed_at',
    ];

    protected $casts = [
        'handed_over_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
