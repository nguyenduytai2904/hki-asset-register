<?php

declare(strict_types=1);

namespace Hki\AssetRegister\Asset\Http\Requests;

use App\Support\Account\AccountContext;
use Hki\AssetRegister\Asset\Models\Asset;
use Illuminate\Foundation\Http\FormRequest;

final class AssetSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && $user->can('accessSettings', [Asset::class, AccountContext::getOrFail()]);
    }

    public function rules(): array
    {
        return [];
    }
}
