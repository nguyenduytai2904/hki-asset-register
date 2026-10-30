<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

final class AssetMineRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('useSelfService', [Asset::class, AccountContext::getOrFail()]);
    }

    public function rules(): array
    {
        return [
            'per_page' => ['required', 'integer', 'between:1,100'],
            'page' => ['required', 'integer', 'min:1'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $perPage = filter_var($this->query('per_page', 15), FILTER_VALIDATE_INT);
        $page = filter_var($this->query('page', 1), FILTER_VALIDATE_INT);

        $this->merge([
            'per_page' => max(1, min(100, $perPage === false ? 15 : $perPage)),
            'page' => max(1, $page === false ? 1 : $page),
        ]);
    }
}
