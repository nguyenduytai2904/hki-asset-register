<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use App\Support\Timezone\TimezoneContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class DisposeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('manageAsset', [
            Asset::class,
            (int) $this->route('assetId'),
            AccountContext::getOrFail(),
        ]);
    }

    public function rules(): array
    {
        $accountId = AccountContext::getOrFail();
        $asset = Asset::query()
            ->where('account_id', $accountId)
            ->findOrFail((int) $this->route('assetId'));
        $disposedAtRules = [
            'required',
            'date_format:Y-m-d',
            'before_or_equal:'.TimezoneContext::now()->toDateString(),
        ];

        if ($asset->purchase_date !== null) {
            $disposedAtRules[] = 'after_or_equal:'.$asset->purchase_date->toDateString();
        }

        return [
            'disposed_at' => $disposedAtRules,
            'disposal_method' => ['required', 'string', Rule::in(['sale', 'destroy', 'gift', 'return', 'other'])],
            'disposal_basis' => ['required', 'string', 'min:1', 'max:500'],
            '_asset_register_dialog' => ['required', Rule::in(['dispose'])],
        ];
    }
}
