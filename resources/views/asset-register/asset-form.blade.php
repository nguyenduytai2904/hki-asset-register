@extends('layouts.app')

@section('title', $asset ? __('asset-register::asset-register.asset_form.edit_title') : __('asset-register::asset-register.asset_form.create_title'))

@section('content')
<main class="ar-page">
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
            <h1>{{ $asset ? __('asset-register::asset-register.asset_form.edit_title') : __('asset-register::asset-register.asset_form.create_title') }}</h1>
            <p>{{ __('asset-register::asset-register.asset_form.description') }}</p>
        </div>
    </header>

    @if (session('success'))
        <div class="ar-alert ar-alert-success" role="status">{{ session('success') }}</div>
    @endif

    <form class="ar-card ar-asset-form" method="POST" action="{{ $asset ? route('asset-register.assets.update', $asset->id) : route('asset-register.assets.store') }}">
        @csrf
        @if ($asset)
            @method('PUT')
        @endif
        <section class="ar-form-section">
            <h2>{{ __('asset-register::asset-register.asset_form.general') }}</h2>
            <div class="ar-form-grid">
                <label>{{ __('asset-register::asset-register.asset_form.type') }} <span class="ar-required">*</span>
                    <select id="ar-asset-type" name="asset_type_id" required>
                        <option value="">{{ __('asset-register::asset-register.asset_form.choose_type') }}</option>
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" data-next-code="{{ $nextCodes[$type->id] ?? '' }}" @selected((string) old('asset_type_id', $asset?->asset_type_id) === (string) $type->id)>{{ $type->name }}@if ($type->status === 'inactive') · {{ __('asset-register::asset-register.settings.inactive') }}@endif</option>
                        @endforeach
                    </select>
                    @error('asset_type_id')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label>{{ __('asset-register::asset-register.asset_form.code') }}@if ($asset)<input value="{{ $asset->code }}" readonly>@else<input name="code" maxlength="30" value="{{ old('code') }}" placeholder="{{ __('asset-register::asset-register.asset_form.auto_code') }}">@endif
                    @if (! $asset)
                        <small class="ar-form-hint">{{ __('asset-register::asset-register.asset_form.next_code') }} <output id="ar-next-code">--</output></small>
                    @endif
                    @error('code')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label class="ar-form-span">{{ __('asset-register::asset-register.asset_form.name') }} <span class="ar-required">*</span><input name="name" maxlength="150" required value="{{ old('name', $asset?->name) }}">@error('name')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.brand') }}<input name="brand" maxlength="100" value="{{ old('brand', $asset?->brand) }}">@error('brand')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.model') }}<input name="model" maxlength="100" value="{{ old('model', $asset?->model) }}">@error('model')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.serial') }}<input name="serial" maxlength="100" value="{{ old('serial', $asset?->serial) }}">@error('serial')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.condition') }} <span class="ar-required">*</span>
                    <select name="condition" required>
                        @foreach (['new', 'good', 'fair', 'poor'] as $condition)
                            <option value="{{ $condition }}" @selected(old('condition', $asset?->condition ?? 'new') === $condition)>{{ __('asset-register::asset-register.condition.' . $condition) }}</option>
                        @endforeach
                    </select>
                    @error('condition')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label>{{ __('asset-register::asset-register.asset_form.location') }}
                    <select name="asset_location_id">
                        <option value="">{{ __('asset-register::asset-register.asset_form.no_location') }}</option>
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected((string) old('asset_location_id', $asset?->asset_location_id) === (string) $location->id)>{{ $location->name }}@if ($location->status === 'inactive') · {{ __('asset-register::asset-register.settings.inactive') }}@endif</option>
                        @endforeach
                    </select>
                    @error('asset_location_id')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label class="ar-form-span">{{ __('asset-register::asset-register.asset_form.configuration') }}<textarea name="configuration" maxlength="2000" rows="3">{{ old('configuration', $asset?->configuration) }}</textarea>@error('configuration')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
            </div>
        </section>
        <section class="ar-form-section">
            <h2>{{ __('asset-register::asset-register.asset_form.purchase_warranty') }}</h2>
            <div class="ar-form-grid">
                <label>{{ __('asset-register::asset-register.asset_form.purchase_date') }}<input type="date" name="purchase_date" value="{{ old('purchase_date', $asset?->purchase_date?->format('Y-m-d')) }}">@error('purchase_date')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.warranty_end_date') }}<input type="date" name="warranty_end_date" value="{{ old('warranty_end_date', $asset?->warranty_end_date?->format('Y-m-d')) }}">@error('warranty_end_date')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.supplier') }}<input name="supplier" maxlength="150" value="{{ old('supplier', $asset?->supplier) }}">@error('supplier')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.receipt_number') }}<input name="receipt_number" maxlength="100" value="{{ old('receipt_number', $asset?->receipt_number) }}">@error('receipt_number')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
            </div>
        </section>
        <section class="ar-form-section">
            <h2>{{ __('asset-register::asset-register.asset_form.additional') }}</h2>
            <label>{{ __('asset-register::asset-register.asset_form.note') }}<textarea name="note" maxlength="2000" rows="3">{{ old('note', $asset?->note) }}</textarea>@error('note')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
        </section>
        <footer class="ar-form-footer">
            <a class="ar-button" href="{{ $asset ? route('asset-register.assets.show', $asset->id) : route('asset-register.registry') }}">{{ __('asset-register::asset-register.asset_form.cancel') }}</a>
            <button class="ar-button ar-button-dark" type="submit" @disabled(! $asset && $types->isEmpty())>{{ $asset ? __('asset-register::asset-register.settings.save') : __('asset-register::asset-register.registry.add_asset') }}</button>
        </footer>
    </form>
</main>
@if (! $asset)
    <script>
        (() => {
            const typeSelect = document.getElementById('ar-asset-type');
            const preview = document.getElementById('ar-next-code');
            const updatePreview = () => {
                preview.textContent = typeSelect.selectedOptions[0]?.dataset.nextCode || '--';
            };
            typeSelect.addEventListener('change', updatePreview);
            updatePreview();
        })();
    </script>
@endif
@include('asset-register::asset-register.styles')
@endsection
