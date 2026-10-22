<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Asset extends Model
{
    protected $table = 'assets';

    protected $fillable = [
        'account_id', 'asset_type_id', 'asset_location_id', 'code', 'name',
        'brand', 'model', 'serial', 'condition', 'status', 'current_keeper_id',
        'warranty_end_date', 'purchase_date', 'configuration', 'supplier',
        'receipt_number', 'note', 'disposed_at', 'disposal_method', 'disposal_basis',
    ];

    protected $casts = [
        'disposed_at' => 'date',
        'purchase_date' => 'date',
        'warranty_end_date' => 'date',
    ];

    public function keeper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_keeper_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }

    public function handovers(): HasMany
    {
        return $this->hasMany(AssetHandover::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AssetHistory::class);
    }
}
