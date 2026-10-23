@extends('layouts.app')

@section('title', __('asset-register::asset-register.settings.title'))

@section('content')
<main class="ar-page ar-settings-page">
    <nav class="ar-tabs" aria-label="{{ __('asset-register::asset-register.name') }}">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class(['ar-tab', 'is-active' => $activeTab === $tab['key']])>
                <span class="material-symbols-rounded" aria-hidden="true">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <header class="ar-heading ar-page-heading">
        <div>
            <h1>{{ __('asset-register::asset-register.settings.title') }}</h1>
            <p>{{ __('asset-register::asset-register.settings.description') }}</p>
        </div>
    </header>

    @if (session('success'))
        <div class="ar-alert ar-alert-success" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="ar-alert ar-alert-error" role="alert">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    @php($assetTypeIcons = \Hki\AssetRegister\Asset\Models\AssetType::availableIcons())
    <section class="ar-card ar-settings-card" aria-labelledby="asset-types-heading">
        <div class="ar-card-heading">
            <h2 id="asset-types-heading">{{ __('asset-register::asset-register.settings.types') }}</h2>
            @if ($canManageCatalog)
                <button class="ar-button ar-button-dark" type="button" data-ar-open-dialog="ar-create-type-dialog"><span class="material-symbols-rounded" aria-hidden="true">add</span>{{ __('asset-register::asset-register.settings.add_type') }}</button>
            @endif
        </div>
        @if (! $canManageCatalog)
            <p class="ar-form-hint ar-settings-permission-hint">{{ __('asset-register::asset-register.settings.manage_requires_view') }}</p>
        @endif
        @if ($canManageCatalog)
        <dialog class="ar-dialog" id="ar-create-type-dialog" aria-labelledby="ar-create-type-title">
            <header class="ar-dialog-header">
                <h2 id="ar-create-type-title">{{ __('asset-register::asset-register.settings.add_type') }}</h2>
                <button class="ar-dialog-close" type="button" data-ar-close-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
            </header>
            <form class="ar-form ar-dialog-form" method="POST" action="{{ route('asset-register.settings.asset-types.store') }}">
                    @csrf
                    <input type="hidden" name="_asset_register_dialog" value="create-type">
                    <label>{{ __('asset-register::asset-register.settings.name') }} <input name="name" maxlength="100" required value="{{ old('name') }}">@error('name')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                    <label>{{ __('asset-register::asset-register.settings.prefix') }} <input name="prefix" minlength="2" maxlength="6" required value="{{ old('prefix') }}">@error('prefix')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                    <small class="ar-form-hint">{{ __('asset-register::asset-register.settings.first_code_preview') }} <output data-ar-first-code>{{ old('prefix') ? strtoupper(old('prefix')).'-0001' : '--' }}</output></small>
                    <fieldset class="ar-icon-picker">
                        <legend>{{ __('asset-register::asset-register.settings.icon') }}</legend>
                        <div class="ar-icon-picker-options">
                            @foreach ($assetTypeIcons as $icon)
                                <label>
                                    <input type="radio" name="icon" value="{{ $icon }}" required @checked(old('icon', 'category') === $icon)>
                                    <span class="material-symbols-rounded" aria-hidden="true">{{ $icon }}</span>
                                    <span class="sr-only">{{ $icon }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                    <label>{{ __('asset-register::asset-register.settings.description_label') }} <input name="description" maxlength="500" value="{{ old('description') }}"></label>
                    <footer class="ar-dialog-footer">
                        <button class="ar-button" type="button" data-ar-close-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
                        <button class="ar-button ar-button-dark" type="submit">{{ __('asset-register::asset-register.settings.create') }}</button>
                    </footer>
            </form>
        </dialog>
        @endif
        <div class="ar-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('asset-register::asset-register.settings.type') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.prefix') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.next_code') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.asset_count') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.status') }}</th>
                        <th><span class="sr-only">{{ __('asset-register::asset-register.settings.edit') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($types as $type)
                        <tr>
                            <td><span class="material-symbols-rounded ar-icon" aria-hidden="true">{{ $type->displayIcon() }}</span>{{ $type->name }}</td>
                            <td><strong>{{ $type->prefix }}</strong></td>
                            <td class="ar-muted">{{ $type->next_code }}</td>
                            <td>{{ $type->assets_count }}</td>
                            <td><span @class(['ar-status', 'is-inactive' => $type->status === 'inactive'])>{{ __('asset-register::asset-register.settings.' . $type->status) }}</span></td>
                            <td class="ar-actions">
                                @if ($canManageCatalog)
                                <details>
                                    <summary aria-label="{{ __('asset-register::asset-register.settings.edit') }}">⋮</summary>
                                    <div class="ar-action-menu">
                                        <form method="POST" action="{{ route('asset-register.settings.asset-types.update', $type->id) }}">
                                            @csrf @method('PUT')
                                            <label>{{ __('asset-register::asset-register.settings.name') }} <input name="name" maxlength="100" required value="{{ $type->name }}">@error('name')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                                            <label>{{ __('asset-register::asset-register.settings.prefix') }}
                                                @if ($type->assets_count > 0)
                                                    <input type="hidden" name="prefix" value="{{ $type->prefix }}">
                                                @endif
                                                <input name="{{ $type->assets_count > 0 ? 'prefix_display' : 'prefix' }}" minlength="2" maxlength="6" required value="{{ $type->prefix }}" @readonly($type->assets_count > 0)>
                                                @if ($type->assets_count > 0)
                                                    <small class="ar-form-hint">{{ __('asset-register::asset-register.settings.prefix_in_use', ['count' => $type->assets_count]) }}</small>
                                                @endif
                                                @error('prefix')<small class="ar-field-error">{{ $message }}</small>@enderror
                                            </label>
                                            <label>{{ __('asset-register::asset-register.settings.icon') }}
                                                <select name="icon">
                                                    @if (! in_array($type->icon, $assetTypeIcons, true))
                                                        <option value="{{ $type->icon }}" selected>{{ $type->icon }}</option>
                                                    @endif
                                                    @foreach ($assetTypeIcons as $icon)
                                                        <option value="{{ $icon }}" @selected($type->icon === $icon)>{{ $icon }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label>{{ __('asset-register::asset-register.settings.description_label') }} <input name="description" maxlength="500" value="{{ $type->description }}"></label>
                                            <label>{{ __('asset-register::asset-register.settings.status') }}
                                                <select name="status">
                                                    <option value="active" @selected($type->status === 'active')>{{ __('asset-register::asset-register.settings.active') }}</option>
                                                    <option value="inactive" @selected($type->status === 'inactive')>{{ __('asset-register::asset-register.settings.inactive') }}</option>
                                                </select>
                                            </label>
                                            <button class="ar-button ar-button-dark" type="submit">{{ __('asset-register::asset-register.settings.save') }}</button>
                                        </form>
                                        @if ($type->assets_count === 0)
                                            <form method="POST" action="{{ route('asset-register.settings.asset-types.delete', $type->id) }}">
                                                @csrf @method('DELETE')
                                                <button class="ar-button ar-button-danger" type="submit" onclick="return window.confirm(@js(__('asset-register::asset-register.messages.confirm_delete_type')))">{{ __('asset-register::asset-register.settings.delete') }}</button>
                                            </form>
                                        @else
                                            <button class="ar-button ar-button-danger" type="button" disabled>{{ __('asset-register::asset-register.settings.delete') }}</button>
                                            <span class="ar-form-hint">{{ __('asset-register::asset-register.settings.cannot_delete_in_use', ['count' => $type->assets_count]) }}</span>
                                        @endif
                                    </div>
                                </details>
                                @else
                                    <span class="ar-muted">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="ar-empty">{{ __('asset-register::asset-register.settings.empty_types') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="ar-card ar-settings-card" aria-labelledby="asset-locations-heading">
        <div class="ar-card-heading">
            <h2 id="asset-locations-heading">{{ __('asset-register::asset-register.settings.locations') }}</h2>
            @if ($canManageCatalog)
                <button class="ar-button ar-button-dark" type="button" data-ar-open-dialog="ar-create-location-dialog"><span class="material-symbols-rounded" aria-hidden="true">add</span>{{ __('asset-register::asset-register.settings.add_location') }}</button>
            @endif
        </div>
        @if (! $canManageCatalog)
            <p class="ar-form-hint ar-settings-permission-hint">{{ __('asset-register::asset-register.settings.manage_requires_view') }}</p>
        @endif
        @if ($canManageCatalog)
        <dialog class="ar-dialog" id="ar-create-location-dialog" aria-labelledby="ar-create-location-title">
            <header class="ar-dialog-header">
                <h2 id="ar-create-location-title">{{ __('asset-register::asset-register.settings.add_location') }}</h2>
                <button class="ar-dialog-close" type="button" data-ar-close-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
            </header>
            <form class="ar-form ar-dialog-form" method="POST" action="{{ route('asset-register.settings.locations.store') }}">
                    @csrf
                    <input type="hidden" name="_asset_register_dialog" value="create-location">
                    <label>{{ __('asset-register::asset-register.settings.name') }} <input name="name" maxlength="150" required value="{{ old('name') }}">@error('name')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                    <label>{{ __('asset-register::asset-register.settings.description_label') }} <input name="description" maxlength="500" value="{{ old('description') }}"></label>
                    <footer class="ar-dialog-footer">
                        <button class="ar-button" type="button" data-ar-close-dialog>{{ __('asset-register::asset-register.asset_form.cancel') }}</button>
                        <button class="ar-button ar-button-dark" type="submit">{{ __('asset-register::asset-register.settings.create') }}</button>
                    </footer>
            </form>
        </dialog>
        @endif
        <div class="ar-table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('asset-register::asset-register.settings.name') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.description_label') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.asset_count') }}</th>
                        <th>{{ __('asset-register::asset-register.settings.status') }}</th>
                        <th><span class="sr-only">{{ __('asset-register::asset-register.settings.edit') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($locations as $location)
                        <tr>
                            <td><span class="material-symbols-rounded ar-icon" aria-hidden="true">location_on</span>{{ $location->name }}</td>
                            <td>{{ $location->description ?: __('asset-register::asset-register.settings.no_description') }}</td>
                            <td>{{ $location->assets_count }}</td>
                            <td><span @class(['ar-status', 'is-inactive' => $location->status === 'inactive'])>{{ __('asset-register::asset-register.settings.' . $location->status) }}</span></td>
                            <td class="ar-actions">
                                @if ($canManageCatalog)
                                <details>
                                    <summary aria-label="{{ __('asset-register::asset-register.settings.edit') }}">⋮</summary>
                                    <div class="ar-action-menu">
                                        <form method="POST" action="{{ route('asset-register.settings.locations.update', $location->id) }}">
                                            @csrf @method('PUT')
                                            <label>{{ __('asset-register::asset-register.settings.name') }} <input name="name" maxlength="150" required value="{{ $location->name }}">@error('name')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                                            <label>{{ __('asset-register::asset-register.settings.description_label') }} <input name="description" maxlength="500" value="{{ $location->description }}"></label>
                                            <label>{{ __('asset-register::asset-register.settings.status') }}
                                                <select name="status">
                                                    <option value="active" @selected($location->status === 'active')>{{ __('asset-register::asset-register.settings.active') }}</option>
                                                    <option value="inactive" @selected($location->status === 'inactive')>{{ __('asset-register::asset-register.settings.inactive') }}</option>
                                                </select>
                                            </label>
                                            <button class="ar-button ar-button-dark" type="submit">{{ __('asset-register::asset-register.settings.save') }}</button>
                                        </form>
                                        @if ($location->assets_count === 0)
                                            <form method="POST" action="{{ route('asset-register.settings.locations.delete', $location->id) }}">
                                                @csrf @method('DELETE')
                                                <button class="ar-button ar-button-danger" type="submit" onclick="return window.confirm(@js(__('asset-register::asset-register.messages.confirm_delete_location')))">{{ __('asset-register::asset-register.settings.delete') }}</button>
                                            </form>
                                        @else
                                            <button class="ar-button ar-button-danger" type="button" disabled>{{ __('asset-register::asset-register.settings.delete') }}</button>
                                            <span class="ar-form-hint">{{ __('asset-register::asset-register.settings.cannot_delete_in_use', ['count' => $location->assets_count]) }}</span>
                                        @endif
                                    </div>
                                </details>
                                @else
                                    <span class="ar-muted">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="ar-empty">{{ __('asset-register::asset-register.settings.empty_locations') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
    (() => {
        const failedDialog = @js(old('_asset_register_dialog'));
        const prefixInput = document.querySelector('#ar-create-type-dialog input[name="prefix"]');
        const firstCode = document.querySelector('[data-ar-first-code]');
        prefixInput?.addEventListener('input', () => {
            const prefix = prefixInput.value.trim().toUpperCase();
            if (firstCode) firstCode.textContent = prefix ? `${prefix}-0001` : '--';
        });

        document.addEventListener('click', (event) => {
            if (!(event.target instanceof Element)) return;
            const openButton = event.target.closest('[data-ar-open-dialog]');
            if (openButton) {
                document.getElementById(openButton.dataset.arOpenDialog)?.showModal();
                return;
            }

            const closeButton = event.target.closest('[data-ar-close-dialog]');
            if (closeButton) closeButton.closest('dialog')?.close();
        });

        document.querySelectorAll('.ar-dialog').forEach((dialog) => {
            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        });

        if (failedDialog) document.getElementById(`ar-${failedDialog}-dialog`)?.showModal();
    })();
</script>
@include('asset-register::asset-register.styles')
@endsection
