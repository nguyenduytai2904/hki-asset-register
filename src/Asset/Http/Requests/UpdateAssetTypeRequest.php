<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Hki\AssetRegister\Asset\Models\AssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateAssetTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('manageCatalog', [Asset::class, AccountContext::getOrFail()]);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'prefix' => strtoupper(trim((string) $this->input('prefix', ''))),
        ]);
    }

    public function rules(): array
    {
        $accountId = AccountContext::getOrFail();
        $type = AssetType::query()->where('account_id', $accountId)->findOrFail((int) $this->route('typeId'));

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('asset_register_types', 'name')->where('account_id', $accountId)->ignore($type->id)],
            'prefix' => ['required', 'string', 'regex:/^[A-Z0-9]{2,6}$/', Rule::unique('asset_register_types', 'prefix')->where('account_id', $accountId)->ignore($type->id)],
            'icon' => ['required', 'string', Rule::in(AssetType::availableIcons())],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }
}
