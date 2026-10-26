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
        $query = $this->query->all();
        if (! array_key_exists('q', $query) && array_key_exists('search', $query)) {
            $this->merge(['q' => $query['search']]);
        }

        $filters = [];
        foreach (['status', 'type', 'location', 'warranty'] as $key) {
            $value = $this->input($key);
            $filters[$key] = is_array($value)
                ? array_values($value)
                : ($value === null || $value === '' ? [] : [$value]);
        }

        $this->merge($filters);

        $perPage = filter_var($this->query('per_page', 15), FILTER_VALIDATE_INT);
        $this->merge(['per_page' => max(1, min(100, $perPage === false ? 15 : $perPage))]);

        $page = filter_var($this->query('page', 1), FILTER_VALIDATE_INT);
        $this->merge(['page' => max(1, $page === false ? 1 : $page)]);

        if (! $this->query->has('sort_by') && $this->query->has('sort')) {
            $this->merge(['sort_by' => $this->query('sort')]);
        }
        if (! $this->query->has('sort_dir') && $this->query->has('direction')) {
            $this->merge(['sort_dir' => $this->query('direction')]);
        }
    }

    public function rules(): array
    {
        $accountId = AccountContext::getOrFail();

        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['array', 'max:7'],
            'status.*' => ['string', Rule::in(['available', 'in_use', 'pending', 'in_repair', 'broken', 'lost', 'disposed'])],
            'type' => ['array'],
            'type.*' => ['integer', Rule::exists('asset_register_types', 'id')->where('account_id', $accountId)],
            'location' => ['array'],
            'location.*' => ['integer', Rule::exists('asset_register_locations', 'id')->where('account_id', $accountId)],
            'warranty' => ['array', 'max:2'],
            'warranty.*' => ['string', Rule::in(['expiring', 'expired'])],
            'sort_by' => ['nullable', 'string'],
            'sort_dir' => ['nullable', 'string'],
            'per_page' => ['required', 'integer', 'between:1,100'],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }
}
