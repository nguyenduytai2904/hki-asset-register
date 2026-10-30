@extends('layouts.app')

@section('title', __('asset-register::asset-register.tabs.' . $activeTab))

@section('content')
<main
    class="ar-page"
    x-data="window.HK.assetRegister.registryPage({
        i18n: {
            filterError: @js(__('asset-register::asset-register.registry.filter_error')),
            saveError: @js(__('asset-register::asset-register.registry.asset_save_error')),
        },
    })"
>
    <nav class="ar-tabs" aria-label="{{ __('asset-register::asset-register.name') }}">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class(['ar-tab', 'is-active' => $activeTab === $tab['key']])>
                <span class="material-symbols-rounded" aria-hidden="true">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    @if ($activeTab === 'mine')
        @include('asset-register::asset-register.partials.mine-assets')
    @else
        <header class="ar-heading ar-page-heading">
            <div>
                <h1>{{ __('asset-register::asset-register.registry.title') }}</h1>
                <p>{{ __('asset-register::asset-register.registry.description') }}</p>
            </div>
            @if ($canManage)
                <div class="ar-heading-actions">
                    <a class="ar-button ar-button-dark ar-add-asset" href="{{ route('asset-register.assets.create') }}">
                        <span class="material-symbols-rounded" aria-hidden="true">add</span>{{ __('asset-register::asset-register.registry.add_asset') }}
                    </a>
                </div>
            @endif
        </header>

        <div x-data data-hk-ajax-table data-hk-ajax-table-url="{{ route('asset-register.registry') }}">
            <div class="ar-registry-toolbar">
                <x-hk.table-search
                    name="q"
                    :value="$filters['q']"
                    :placeholder="__('asset-register::asset-register.registry.search')"
                    :action="route('asset-register.registry')"
                />
                <span class="hk-table-filter ar-multi-filter">
                    <select class="form-select form-select-sm hk-ts-sm" name="status[]" data-hk-table-filter="status[]" multiple
                            x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_statuses')) }"
                            aria-label="{{ __('asset-register::asset-register.registry.all_statuses') }}">
                        @foreach (['available', 'pending', 'in_use', 'in_repair', 'broken', 'lost', 'disposed'] as $status)
                            <option value="{{ $status }}" @selected(in_array($status, $filters['status'], true))>{{ __('asset-register::asset-register.asset_status.' . $status) }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-rounded ar-filter-chevron" aria-hidden="true">expand_more</span>
                </span>
                <span class="hk-table-filter ar-multi-filter">
                    <select class="form-select form-select-sm hk-ts-sm" name="type[]" data-hk-table-filter="type[]" multiple
                            x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_types')) }"
                            aria-label="{{ __('asset-register::asset-register.registry.all_types') }}">
                        @foreach ($types as $type)
                            <option value="{{ $type->id }}" @selected(in_array((string) $type->id, $filters['type'], true))>{{ $type->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-rounded ar-filter-chevron" aria-hidden="true">expand_more</span>
                </span>
                <span class="hk-table-filter ar-multi-filter">
                    <select class="form-select form-select-sm hk-ts-sm" name="location[]" data-hk-table-filter="location[]" multiple
                            x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_locations')) }"
                            aria-label="{{ __('asset-register::asset-register.registry.all_locations') }}">
                        @foreach ($locations as $location)
                            <option value="{{ $location->id }}" @selected(in_array((string) $location->id, $filters['location'], true))>{{ $location->name }}</option>
                        @endforeach
                    </select>
                    <span class="material-symbols-rounded ar-filter-chevron" aria-hidden="true">expand_more</span>
                </span>
                <span class="hk-table-filter ar-multi-filter">
                    <select class="form-select form-select-sm hk-ts-sm" name="warranty[]" data-hk-table-filter="warranty[]" multiple
                            x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_warranties')) }"
                            aria-label="{{ __('asset-register::asset-register.registry.all_warranties') }}">
                        <option value="expiring" @selected(in_array('expiring', $filters['warranty'], true))>{{ __('asset-register::asset-register.registry.warranty_expiring') }}</option>
                        <option value="expired" @selected(in_array('expired', $filters['warranty'], true))>{{ __('asset-register::asset-register.registry.warranty_expired') }}</option>
                    </select>
                    <span class="material-symbols-rounded ar-filter-chevron" aria-hidden="true">expand_more</span>
                </span>
                <x-hk.table-per-page :per-page="$filters['per_page']" :options="[15, 30, 50, 100]" />
            </div>
            @include('asset-register::asset-register.partials.registry-table-region')
        </div>
    @endif
@if ($activeTab === 'registry')
    @if ($canManage)
        @include('asset-register::asset-register.partials.asset-form-dialog')
    @endif
@endif
</main>
@endsection
