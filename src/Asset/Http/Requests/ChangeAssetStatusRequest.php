<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ChangeAssetStatusRequest extends FormRequest
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
        return [
            'status' => ['required', 'string', Rule::in(['available', 'in_repair', 'broken', 'lost'])],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            '_asset_register_dialog' => ['required', Rule::in(['status'])],
        ];
    }
}
