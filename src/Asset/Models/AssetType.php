<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Models;

use App\Support\Account\Concerns\BelongsToAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AssetType extends Model
{
    use BelongsToAccount;

    private const DISPLAY_ICONS = [
        'category',
        'computer',
        'desktop_windows',
        'devices_other',
        'dns',
        'inventory_2',
        'keyboard',
        'laptop',
        'laptop_mac',
        'router',
        'smartphone',
        'tablet',
        'tablet_mac',
    ];

    /** @return list<string> */
    public static function availableIcons(): array
    {
        return self::DISPLAY_ICONS;
    }

    protected $table = 'asset_register_types';

    protected $fillable = ['account_id', 'name', 'prefix', 'icon', 'status', 'description'];

    public function displayIcon(): string
    {
        $icon = (string) $this->icon;

        return $icon === ''
            ? 'category'
            : (in_array($icon, self::DISPLAY_ICONS, true) ? $icon : 'devices_other');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'asset_type_id');
    }
}
