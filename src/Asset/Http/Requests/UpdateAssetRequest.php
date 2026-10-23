<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAssetRequest extends FormRequest
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
        $asset = Asset::query()->where('account_id', $accountId)->findOrFail((int) $this->route('assetId'));
        $typeId = (int) $asset->asset_type_id;
        $locationId = $asset->asset_location_id === null ? null : (int) $asset->asset_location_id;

        return [
            'asset_type_id' => [
                'required',
                'integer',
                Rule::exists('asset_types', 'id')->where(fn ($query) => $query
                    ->where('account_id', $accountId)
                    ->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $typeId))),
            ],
            'asset_location_id' => [
                'nullable',
                'integer',
                Rule::exists('asset_locations', 'id')->where(fn ($query) => $query
                    ->where('account_id', $accountId)
                    ->where(fn ($query) => $query->where('status', 'active')->orWhere('id', $locationId))),
            ],
            'name' => ['required', 'string', 'min:1', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial' => ['nullable', 'string', 'max:100'],
            'configuration' => ['nullable', 'string', 'max:2000'],
            'condition' => ['required', Rule::in(['new', 'good', 'fair', 'poor'])],
            'purchase_date' => ['nullable', 'date'],
            'warranty_end_date' => ['nullable', 'date', 'after_or_equal:purchase_date'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
