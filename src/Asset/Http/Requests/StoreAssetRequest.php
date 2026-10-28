<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('manageRegistry', [Asset::class, AccountContext::getOrFail()]);
    }

    protected function prepareForValidation(): void
    {
        $code = strtoupper(trim((string) $this->input('code', '')));
        $durationPreset = (string) $this->input('warranty_duration_preset', '');
        $durationMonths = $durationPreset === 'custom'
            ? $this->input('warranty_duration_custom_months')
            : $durationPreset;

        $this->merge([
            'code' => $code === '' ? null : $code,
            'warranty_duration_months' => $durationMonths === '' ? null : $durationMonths,
        ]);
    }

    public function rules(): array
    {
        $accountId = AccountContext::getOrFail();

        return [
            'asset_type_id' => ['required', 'integer', Rule::exists('asset_register_types', 'id')->where('account_id', $accountId)->where('status', 'active')],
            'asset_location_id' => ['nullable', 'integer', Rule::exists('asset_register_locations', 'id')->where('account_id', $accountId)->where('status', 'active')],
            'code' => ['nullable', 'string', 'min:2', 'max:30', 'regex:/^[A-Z0-9][A-Z0-9-]*$/', Rule::unique('asset_register_assets', 'code')->where('account_id', $accountId)],
            'name' => ['required', 'string', 'min:1', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial' => ['nullable', 'string', 'max:100'],
            'configuration' => ['nullable', 'string', 'max:2000'],
            'condition' => ['required', Rule::in(['new', 'good', 'fair', 'poor'])],
            'purchase_date' => ['nullable', 'required_if:warranty_duration_preset,3,6,12,24,custom', 'date_format:Y-m-d'],
            'warranty_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:purchase_date'],
            'warranty_duration_preset' => ['nullable', Rule::in(['3', '6', '12', '24', 'custom'])],
            'warranty_duration_custom_months' => ['exclude_unless:warranty_duration_preset,custom', 'required', 'integer', 'between:1,1200'],
            'warranty_duration_months' => ['nullable', 'integer', 'between:1,1200'],
            'supplier' => ['nullable', 'string', 'max:150'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
