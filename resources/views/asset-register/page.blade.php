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
        <header class="ar-heading ar-page-heading">
            <div>
                <h1>{{ __('asset-register::asset-register.mine.title') }}</h1>
                <p>{{ __('asset-register::asset-register.mine.description') }}</p>
            </div>
            <div class="ar-heading-actions">
                <span class="ar-preview-label">{{ __('asset-register::asset-register.preview_label') }}</span>
            </div>
        </header>
        <section class="ar-view-content ar-mine-content">
        @if ($preview['pending'])
            <section class="ar-pending-card" aria-labelledby="ar-pending-heading">
                <h2 id="ar-pending-heading">
                    <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
                    {{ __('asset-register::asset-register.mine.pending', ['count' => count($preview['pending'])]) }}
                </h2>
                @foreach ($preview['pending'] as $asset)
                    <div class="ar-pending-item">
                        <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset['icon'] }}</span></span>
                        <div class="ar-pending-asset">
                            <a class="ar-preview-asset-link" href="{{ route('asset-register.mine.preview', ['assetCode' => $asset['code']]) }}"><strong>{{ $asset['code'] }}</strong> · {{ $asset['name'] }}</a>
                            <small>{{ $asset['model'] }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset['serial'] }}</small>
                        </div>
                        <p>{{ __('asset-register::asset-register.mine.handed_over_by', ['name' => $asset['assigned_by'], 'date' => $asset['assigned_at']]) }} · {{ __('asset-register::asset-register.mine.condition_at_handover', ['condition' => $asset['condition']]) }}</p>
                        <div class="ar-preview-actions">
                            <button class="ar-button" type="button" data-ar-open-action-dialog="issue" data-code="{{ $asset['code'] }}" data-name="{{ $asset['name'] }}" data-issue-type="incorrect">
                                <span class="material-symbols-rounded" aria-hidden="true">report</span>{{ __('asset-register::asset-register.mine.report_incorrect') }}
                            </button>
                            <button class="ar-button ar-button-dark" type="button" data-ar-open-action-dialog="confirm" data-code="{{ $asset['code'] }}" data-name="{{ $asset['name'] }}" data-condition="{{ $asset['condition'] }}" data-assigned-by="{{ $asset['assigned_by'] }}" data-assigned-at="{{ $asset['assigned_at'] }}">
                                <span class="material-symbols-rounded" aria-hidden="true">task_alt</span>{{ __('asset-register::asset-register.mine.confirm_received') }}
                            </button>
                        </div>
                    </div>
                @endforeach
            </section>
        @endif
        <section class="ar-card ar-mine-list" aria-label="{{ __('asset-register::asset-register.mine.assets') }}">
            <div class="ar-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('asset-register::asset-register.registry.asset') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.condition') }}</th>
                            <th>{{ __('asset-register::asset-register.mine.received_at') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.status') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.warranty') }}</th>
                            <th><span class="sr-only">{{ __('asset-register::asset-register.registry.details') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preview['assets'] as $asset)
                            <tr>
                                <td class="ar-asset-cell">
                                    <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset['icon'] }}</span></span>
                                    <span><a class="ar-preview-asset-link" href="{{ route('asset-register.mine.preview', ['assetCode' => $asset['code']]) }}"><strong>{{ $asset['code'] }}</strong> · {{ $asset['name'] }}</a><small>{{ $asset['model'] }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset['serial'] }}</small></span>
                                </td>
                                <td>{{ $asset['condition'] }}</td>
                                <td>{{ $asset['received_at'] }}</td>
                                <td><span @class(['ar-status', 'is-pending' => $asset['status'] === 'pending'])>{{ __('asset-register::asset-register.asset_status.' . $asset['status']) }}</span></td>
                                <td>{{ $asset['warranty'] }}</td>
                                <td>
                                    <div class="ar-row-actions">
                                    <button class="ar-button ar-button-quiet" type="button" data-ar-open-action-dialog="issue" data-code="{{ $asset['code'] }}" data-name="{{ $asset['name'] }}" @if ($asset['status'] === 'pending') data-issue-type="incorrect" @endif aria-label="{{ $asset['status'] === 'pending' ? __('asset-register::asset-register.mine.report_incorrect') : __('asset-register::asset-register.mine.report_issue') }}">
                                        <span class="material-symbols-rounded" aria-hidden="true">report</span>
                                    </button>
                                    <a class="ar-row-arrow" href="{{ route('asset-register.mine.preview', ['assetCode' => $asset['code']]) }}" aria-label="{{ __('asset-register::asset-register.registry.details') }}"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr><td colspan="6">{{ __('asset-register::asset-register.mine.asset_count', ['count' => count($preview['assets'])]) }}</td></tr></tfoot>
                </table>
            </div>
        </section>
        </section>
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
@if ($activeTab === 'mine')
    @include('asset-register::asset-register.action-dialogs')
@endif
@if ($activeTab === 'registry')
    @if ($canManage)
        <dialog class="ar-dialog ar-asset-dialog" id="ar-asset-create-dialog" aria-labelledby="ar-asset-dialog-title">
            <header class="ar-dialog-header">
                <h2 id="ar-asset-dialog-title">{{ __('asset-register::asset-register.asset_form.create_title') }}</h2>
                <button class="ar-dialog-close" type="button" data-ar-close-asset-dialog aria-label="{{ __('asset-register::asset-register.asset_form.cancel') }}"><span class="material-symbols-rounded" aria-hidden="true">close</span></button>
            </header>
            <div class="ar-asset-dialog-content" id="ar-asset-dialog-content"></div>
            <div class="ar-dialog-feedback" id="ar-asset-dialog-feedback" role="alert" hidden></div>
        </dialog>
    @endif
@endif
</main>
@endsection
