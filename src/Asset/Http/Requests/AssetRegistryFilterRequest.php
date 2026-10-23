<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssetRegistryFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('viewRegistry', [Asset::class, AccountContext::getOrFail()]);
    }

    protected function prepareForValidation(): void
    {
        $filters = [];
        foreach (['status', 'type', 'location', 'warranty'] as $key) {
            $value = $this->input($key);
            $filters[$key] = is_array($value)
                ? array_values($value)
                : ($value === null || $value === '' ? [] : [$value]);
        }

        $this->merge($filters);
    }

    public function rules(): array
    {
        $accountId = AccountContext::getOrFail();

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['array', 'max:7'],
            'status.*' => ['string', Rule::in(['available', 'in_use', 'pending', 'in_repair', 'broken', 'lost', 'disposed'])],
            'type' => ['array'],
            'type.*' => ['integer', Rule::exists('asset_types', 'id')->where('account_id', $accountId)],
            'location' => ['array'],
            'location.*' => ['integer', Rule::exists('asset_locations', 'id')->where('account_id', $accountId)],
            'warranty' => ['array', 'max:2'],
            'warranty.*' => ['string', Rule::in(['expiring', 'expired'])],
            'sort' => ['nullable', Rule::in(['asset', 'status', 'warranty'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', Rule::in([15, 30, 50])],
        ];
    }
}
