@extends('layouts.app')

@section('title', $asset ? __('asset-register::asset-register.asset_form.edit_title') : __('asset-register::asset-register.asset_form.create_title'))

@section('content')
<main class="ar-page" x-data="window.HK.assetRegister.assetFormPage()">
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

    @php
        $warrantyDurationMonths = old('warranty_duration_months', $asset?->warranty_duration_months);
        $defaultWarrantyPreset = $warrantyDurationMonths === null
            ? ''
            : (in_array((int) $warrantyDurationMonths, [3, 6, 12, 24], true) ? (string) $warrantyDurationMonths : 'custom');
        $warrantyDurationPreset = old('warranty_duration_preset', $defaultWarrantyPreset);
        $customWarrantyDurationMonths = old(
            'warranty_duration_custom_months',
            $warrantyDurationPreset === 'custom' ? $warrantyDurationMonths : '',
        );
    @endphp

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
                        <small class="ar-form-hint">{{ __('asset-register::asset-register.asset_form.next_code') }} <output id="ar-next-code" data-empty-value="--">--</output></small>
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
                <label>{{ __('asset-register::asset-register.asset_form.purchase_date') }} <span class="ar-required" data-ar-warranty-purchase-required @if (! $warrantyDurationPreset) hidden @endif>*</span><input type="date" name="purchase_date" data-hk-datepicker="date" data-ar-warranty-purchase-date value="{{ old('purchase_date', $asset?->purchase_date?->format('Y-m-d')) }}">@error('purchase_date')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.warranty_duration') }}
                    <select id="ar-warranty-duration" name="warranty_duration_preset" data-ar-warranty-duration>
                        <option value="" @selected($warrantyDurationPreset === '')>{{ __('asset-register::asset-register.asset_form.warranty_duration_manual') }}</option>
                        <option value="3" @selected($warrantyDurationPreset === '3')>{{ __('asset-register::asset-register.asset_form.warranty_3_months') }}</option>
                        <option value="6" @selected($warrantyDurationPreset === '6')>{{ __('asset-register::asset-register.asset_form.warranty_6_months') }}</option>
                        <option value="12" @selected($warrantyDurationPreset === '12')>{{ __('asset-register::asset-register.asset_form.warranty_1_year') }}</option>
                        <option value="24" @selected($warrantyDurationPreset === '24')>{{ __('asset-register::asset-register.asset_form.warranty_2_years') }}</option>
                        <option value="custom" @selected($warrantyDurationPreset === 'custom')>{{ __('asset-register::asset-register.asset_form.warranty_custom') }}</option>
                    </select>
                    <input type="hidden" name="warranty_duration_months" value="{{ $warrantyDurationMonths }}">
                    @error('warranty_duration_preset')<small class="ar-field-error">{{ $message }}</small>@enderror
                    @error('warranty_duration_months')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label data-ar-warranty-custom-field @if ($warrantyDurationPreset !== 'custom') hidden @endif>{{ __('asset-register::asset-register.asset_form.warranty_custom_months') }} <span class="ar-required">*</span>
                    <input type="number" name="warranty_duration_custom_months" min="1" max="1200" step="1" data-ar-warranty-custom-months value="{{ $customWarrantyDurationMonths }}" @if ($warrantyDurationPreset === 'custom') required @endif>
                    @error('warranty_duration_custom_months')<small class="ar-field-error">{{ $message }}</small>@enderror
                </label>
                <label>{{ __('asset-register::asset-register.asset_form.warranty_end_date') }}<input type="date" name="warranty_end_date" data-hk-datepicker="date" data-ar-warranty-end-date value="{{ old('warranty_end_date', $asset?->warranty_end_date?->format('Y-m-d')) }}">@error('warranty_end_date')<small class="ar-field-error">{{ $message }}</small>@enderror
                    <small class="ar-form-hint">{{ __('asset-register::asset-register.asset_form.warranty_duration_hint') }}</small>
                </label>
                <label>{{ __('asset-register::asset-register.asset_form.supplier') }}<input name="supplier" maxlength="150" value="{{ old('supplier', $asset?->supplier) }}">@error('supplier')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
                <label>{{ __('asset-register::asset-register.asset_form.receipt_number') }}<input name="receipt_number" maxlength="100" value="{{ old('receipt_number', $asset?->receipt_number) }}">@error('receipt_number')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
            </div>
        </section>
        <section class="ar-form-section">
            <h2>{{ __('asset-register::asset-register.asset_form.additional') }}</h2>
            <label>{{ __('asset-register::asset-register.asset_form.note') }}<textarea name="note" maxlength="2000" rows="3">{{ old('note', $asset?->note) }}</textarea><small class="ar-form-hint">{{ __('asset-register::asset-register.actions.no_personal_data') }}</small>@error('note')<small class="ar-field-error">{{ $message }}</small>@enderror</label>
        </section>
        <footer class="ar-form-footer">
            <a class="ar-button" href="{{ $asset ? route('asset-register.assets.show', $asset->id) : route('asset-register.registry') }}">{{ __('asset-register::asset-register.asset_form.cancel') }}</a>
            <button class="ar-button ar-button-dark" type="submit" @disabled(! $asset && $types->isEmpty())>{{ $asset ? __('asset-register::asset-register.settings.save') : __('asset-register::asset-register.registry.add_asset') }}</button>
        </footer>
    </form>
</main>
@if (! $asset)
@endif
@endsection
