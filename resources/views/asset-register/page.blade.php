@extends('layouts.app')

@section('title', __('asset-register::asset-register.tabs.' . $activeTab))

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
                            <strong>{{ $asset['code'] }}</strong> · {{ $asset['name'] }}
                            <small>{{ $asset['model'] }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset['serial'] }}</small>
                        </div>
                        <p>{{ __('asset-register::asset-register.mine.handed_over_by', ['name' => $asset['assigned_by'], 'date' => $asset['assigned_at']]) }} · {{ __('asset-register::asset-register.mine.condition_at_handover', ['condition' => $asset['condition']]) }}</p>
                        <div class="ar-preview-actions">
                            <button class="ar-button" type="button" disabled title="{{ __('asset-register::asset-register.preview_tooltip') }}">
                                <span class="material-symbols-rounded" aria-hidden="true">report</span>{{ __('asset-register::asset-register.mine.report_issue') }}
                            </button>
                            <button class="ar-button ar-button-dark" type="button" disabled title="{{ __('asset-register::asset-register.preview_tooltip') }}">
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
                                    <span><strong>{{ $asset['code'] }}</strong> · {{ $asset['name'] }}<small>{{ $asset['model'] }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset['serial'] }}</small></span>
                                </td>
                                <td>{{ $asset['condition'] }}</td>
                                <td>{{ $asset['received_at'] }}</td>
                                <td><span @class(['ar-status', 'is-pending' => $asset['status'] === 'pending'])>{{ __('asset-register::asset-register.asset_status.' . $asset['status']) }}</span></td>
                                <td>{{ $asset['warranty'] }}</td>
                                <td class="ar-row-arrow"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></td>
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

        <form class="ar-registry-toolbar" method="GET" action="{{ route('asset-register.registry') }}">
            <label class="ar-search">
                <span class="material-symbols-rounded" aria-hidden="true">search</span>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('asset-register::asset-register.registry.search') }}" aria-label="{{ __('asset-register::asset-register.registry.search') }}">
            </label>
            <select class="form-select form-select-sm hk-ts-sm" name="status[]" multiple aria-label="{{ __('asset-register::asset-register.registry.all_statuses') }}" x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_statuses')) }">
                @foreach (['available', 'pending', 'in_use', 'in_repair', 'broken', 'lost', 'disposed'] as $status)
                    <option value="{{ $status }}" @selected(in_array($status, $filters['status'] ?? [], true))>{{ __('asset-register::asset-register.asset_status.' . $status) }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm hk-ts-sm" name="type[]" multiple aria-label="{{ __('asset-register::asset-register.registry.all_types') }}" x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_types')) }">
                @foreach ($types as $type)
                    <option value="{{ $type->id }}" @selected(in_array((string) $type->id, $filters['type'] ?? [], true))>{{ $type->name }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm hk-ts-sm" name="location[]" multiple aria-label="{{ __('asset-register::asset-register.registry.all_locations') }}" x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_locations')) }">
                @foreach ($locations as $location)
                    <option value="{{ $location->id }}" @selected(in_array((string) $location->id, $filters['location'] ?? [], true))>{{ $location->name }}</option>
                @endforeach
            </select>
            <select class="form-select form-select-sm hk-ts-sm" name="warranty[]" multiple aria-label="{{ __('asset-register::asset-register.registry.all_warranties') }}" x-tom-select="{ placeholder: @js(__('asset-register::asset-register.registry.all_warranties')) }">
                <option value="expiring" @selected(in_array('expiring', $filters['warranty'] ?? [], true))>{{ __('asset-register::asset-register.registry.warranty_expiring') }}</option>
                <option value="expired" @selected(in_array('expired', $filters['warranty'] ?? [], true))>{{ __('asset-register::asset-register.registry.warranty_expired') }}</option>
            </select>
            <input type="hidden" name="sort" value="{{ $filters['sort'] ?? 'asset' }}">
            <input type="hidden" name="direction" value="{{ $filters['direction'] ?? 'asc' }}">
            <label class="ar-page-size">
                <span class="sr-only">{{ __('asset-register::asset-register.registry.per_page') }}</span>
                <select name="per_page">
                    @foreach ([15, 30, 50] as $pageSize)
                        <option value="{{ $pageSize }}" @selected((int) ($filters['per_page'] ?? 15) === $pageSize)>{{ __('asset-register::asset-register.registry.per_page_value', ['count' => $pageSize]) }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button class="ar-button" type="submit">{{ __('asset-register::asset-register.registry.apply_filters') }}</button></noscript>
        </form>

        <div class="ar-registry-results" id="ar-registry-results" aria-live="polite">
        <section class="ar-card ar-registry-table">
            <div class="ar-table-wrap">
                <table>
                    <thead>
                        <tr>
                            @php($assetDirection = ($filters['sort'] ?? 'asset') === 'asset' && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc')
                            @php($statusDirection = ($filters['sort'] ?? '') === 'status' && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc')
                            @php($warrantyDirection = ($filters['sort'] ?? '') === 'warranty' && ($filters['direction'] ?? 'asc') === 'asc' ? 'desc' : 'asc')
                            <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'asset', 'direction' => $assetDirection, 'page' => 1]) }}">{{ __('asset-register::asset-register.registry.asset') }} @if (($filters['sort'] ?? 'asset') === 'asset') {{ $assetDirection === 'asc' ? '↑' : '↓' }} @endif</a></th>
                            <th>{{ __('asset-register::asset-register.registry.type') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.keeper') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.location') }}</th>
                            <th>{{ __('asset-register::asset-register.registry.condition') }}</th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'status', 'direction' => $statusDirection, 'page' => 1]) }}">{{ __('asset-register::asset-register.registry.status') }} @if (($filters['sort'] ?? '') === 'status') {{ $statusDirection === 'asc' ? '↑' : '↓' }} @endif</a></th>
                            <th><a href="{{ request()->fullUrlWithQuery(['sort' => 'warranty', 'direction' => $warrantyDirection, 'page' => 1]) }}">{{ __('asset-register::asset-register.registry.warranty') }} @if (($filters['sort'] ?? '') === 'warranty') {{ $warrantyDirection === 'asc' ? '↑' : '↓' }} @endif</a></th>
                            <th><span class="sr-only">{{ __('asset-register::asset-register.registry.details') }}</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assets as $asset)
                            @php($statusKey = $asset->has_pending_handover ? 'pending' : ($asset->status === 'pending_confirmation' ? 'pending' : $asset->status))
                            <tr>
                                <td class="ar-asset-cell">
                                    <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span></span>
                                    <span><a href="{{ route('asset-register.assets.show', $asset->id) }}"><strong>{{ $asset->code }}</strong> · {{ $asset->name }}</a><small>{{ $asset->brand }} {{ $asset->model }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset->serial }}</small></span>
                                </td>
                                <td><span class="material-symbols-rounded ar-type-icon" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span>{{ $asset->type?->name }}</td>
                                <td>
                                    @if ($asset->keeper)
                                        <span class="ar-avatar">{{ mb_substr($asset->keeper->name, 0, 1) }}</span>
                                        <span @class(['ar-inactive-keeper' => $asset->keeper_is_inactive])>{{ $asset->keeper->name }}@if ($asset->keeper_is_inactive) {{ __('asset-register::asset-register.registry.former_member') }}@endif</span>
                                    @else
                                        <span class="ar-muted">--</span>
                                    @endif
                                </td>
                                <td>{{ $asset->location?->name ?: '--' }}</td>
                                <td>{{ __('asset-register::asset-register.condition.' . $asset->condition) }}</td>
                                <td><span @class(['ar-status', 'is-pending' => $statusKey === 'pending', 'is-warning' => $statusKey === 'in_repair', 'is-danger' => in_array($statusKey, ['broken', 'lost'], true), 'is-inactive' => $statusKey === 'disposed'])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span></td>
                                <td>
                                    @if ($asset->warranty_end_date)
                                        {{ $asset->warranty_end_date->format('d/m/Y') }}
                                        @if ($asset->warranty_end_date->lt(today()))
                                            <small class="ar-warranty-expired">{{ __('asset-register::asset-register.registry.expired') }}</small>
                                        @elseif ($asset->warranty_end_date->lte(today()->addDays(30)))
                                            <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.days_remaining', ['count' => today()->diffInDays($asset->warranty_end_date)]) }}</small>
                                        @endif
                                    @else
                                        --
                                    @endif
                                </td>
                                <td class="ar-row-arrow"><a href="{{ route('asset-register.assets.show', $asset->id) }}" aria-label="{{ __('asset-register::asset-register.registry.details') }}"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="ar-empty">{{ ($filters['q'] ?? '') !== '' ? __('asset-register::asset-register.registry.no_search_results', ['query' => $filters['q']]) : __('asset-register::asset-register.registry.no_assets') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="ar-registry-cards" aria-label="{{ __('asset-register::asset-register.registry.asset') }}">
            @forelse ($assets as $asset)
                @php($statusKey = $asset->has_pending_handover ? 'pending' : ($asset->status === 'pending_confirmation' ? 'pending' : $asset->status))
                <a class="ar-registry-card" href="{{ route('asset-register.assets.show', $asset->id) }}">
                    <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span></span>
                    <span class="ar-registry-card-content">
                        <strong>{{ $asset->code }}</strong> · {{ $asset->name }}
                        <small>{{ $asset->brand }} {{ $asset->model }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset->serial }}</small>
                        <small>{{ __('asset-register::asset-register.registry.location') }}: {{ $asset->location?->name ?: '--' }}</small>
                        <span @class(['ar-status', 'is-pending' => $statusKey === 'pending', 'is-warning' => $statusKey === 'in_repair', 'is-danger' => in_array($statusKey, ['broken', 'lost'], true), 'is-inactive' => $statusKey === 'disposed'])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span>
                        @if ($asset->keeper)
                            <small><span class="ar-avatar">{{ mb_substr($asset->keeper->name, 0, 1) }}</span><span @class(['ar-inactive-keeper' => $asset->keeper_is_inactive])>{{ $asset->keeper->name }}@if ($asset->keeper_is_inactive) {{ __('asset-register::asset-register.registry.former_member') }}@endif</span></small>
                        @endif
                    </span>
                    <span class="material-symbols-rounded ar-card-chevron" aria-hidden="true">chevron_right</span>
                </a>
            @empty
                <p class="ar-empty">{{ ($filters['q'] ?? '') !== '' ? __('asset-register::asset-register.registry.no_search_results', ['query' => $filters['q']]) : __('asset-register::asset-register.registry.no_assets') }}</p>
            @endforelse
        </section>
        <footer class="ar-list-footer">
            @if ($assets->hasPages())
                {{ $assets->links('components.pagination.elegant', ['unit' => __('asset-register::asset-register.registry.asset_unit')]) }}
            @elseif ($assets->total() > 0)
                <div class="hk-pagination-elegant-meta">
                    {{ __('messages.pagination.meta_show') }}
                    <strong>{{ $assets->firstItem() }}</strong>
                    {{ __('messages.pagination.meta_to') }}
                    <strong>{{ $assets->lastItem() }}</strong>
                    {{ __('messages.pagination.meta_total_prefix') }}
                    <strong>{{ $assets->total() }}</strong>
                    {{ __('asset-register::asset-register.registry.asset_unit') }}
                </div>
            @endif
        </footer>
        </div>
    @endif
</main>
@if ($activeTab === 'registry')
    <div class="ar-registry-feedback" id="ar-registry-feedback" role="alert" hidden>{{ __('asset-register::asset-register.registry.filter_error') }}</div>
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
    <script>
        (() => {
            const form = document.querySelector('.ar-registry-toolbar');
            const feedback = document.getElementById('ar-registry-feedback');
            let results = document.getElementById('ar-registry-results');
            let filterTimer;
            let filterRequest;

            if (!form || !results || !feedback) return;

            const updateResults = async (url, pushHistory = true) => {
                filterRequest?.abort();
                filterRequest = new AbortController();
                results.setAttribute('aria-busy', 'true');
                feedback.hidden = true;

                try {
                    const response = await fetch(url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        signal: filterRequest.signal,
                    });
                    if (!response.ok) throw new Error(`Filter request failed: ${response.status}`);

                    const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
                    const replacement = parsed.getElementById('ar-registry-results');
                    if (!replacement) throw new Error('The filter response did not contain asset results.');

                    results.replaceWith(replacement);
                    results = replacement;
                    results.setAttribute('aria-busy', 'false');
                    if (pushHistory) window.history.pushState({}, '', url);

                    const currentUrl = new URL(url, window.location.origin);
                    restoreFilters(currentUrl);
                } catch (error) {
                    if (error.name !== 'AbortError') {
                        console.error(error);
                        feedback.hidden = false;
                        results.setAttribute('aria-busy', 'false');
                    }
                }
            };

            const restoreFilters = (url) => {
                const currentUrl = new URL(url, window.location.origin);
                const defaultValues = { q: '', per_page: '15', sort: 'asset', direction: 'asc' };
                form.querySelectorAll('[name]').forEach((control) => {
                    if (control instanceof HTMLSelectElement && control.multiple) {
                        const legacyName = control.name.replace(/\[\]$/, '');
                        const selectedValues = [
                            ...currentUrl.searchParams.getAll(control.name),
                            ...currentUrl.searchParams.getAll(legacyName),
                        ];
                        if (control.tomselect) {
                            control.tomselect.setValue(selectedValues, true);
                        } else {
                            Array.from(control.options).forEach((option) => {
                                option.selected = selectedValues.includes(option.value);
                            });
                        }
                    } else {
                        control.value = currentUrl.searchParams.get(control.name) || defaultValues[control.name] || '';
                    }
                });
            };

            const submitFilters = () => {
                window.clearTimeout(filterTimer);
                const url = new URL(form.action, window.location.origin);
                new FormData(form).forEach((value, key) => url.searchParams.append(key, value));
                url.searchParams.delete('page');
                updateResults(url.toString());
            };

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                submitFilters();
            });

            form.addEventListener('change', (event) => {
                if (event.target instanceof HTMLSelectElement || event.target instanceof HTMLInputElement) {
                    submitFilters();
                }
            });

            form.querySelector('[name="q"]')?.addEventListener('input', () => {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(submitFilters, 300);
            });

            document.addEventListener('click', (event) => {
                if (!(event.target instanceof Element)) return;
                const link = event.target.closest('#ar-registry-results .ar-pagination a, #ar-registry-results .pagination a, #ar-registry-results thead a');
                if (!link) return;
                event.preventDefault();
                updateResults(link.href);
            });

            window.addEventListener('popstate', () => {
                restoreFilters(window.location.href);
                updateResults(window.location.href, false);
            });

            const assetDialog = document.getElementById('ar-asset-create-dialog');
            const assetDialogContent = document.getElementById('ar-asset-dialog-content');
            const assetDialogFeedback = document.getElementById('ar-asset-dialog-feedback');

            if (!assetDialog || !assetDialogContent || !assetDialogFeedback) return;

            document.addEventListener('click', async (event) => {
                if (!(event.target instanceof Element)) return;
                const addAssetLink = event.target.closest('a.ar-add-asset');
                if (addAssetLink) {
                    event.preventDefault();
                    assetDialogFeedback.hidden = true;

                    try {
                        const response = await fetch(addAssetLink.href, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!response.ok) throw new Error(`Asset form request failed: ${response.status}`);

                        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
                        const sourceForm = parsed.querySelector('.ar-asset-form');
                        const sourceTitle = parsed.querySelector('.ar-page-heading h1');
                        if (!sourceForm || !sourceTitle) throw new Error('The asset form was missing from the response.');

                        document.getElementById('ar-asset-dialog-title').textContent = sourceTitle.textContent;
                        assetDialogContent.replaceChildren(sourceForm.cloneNode(true));
                        const cancelLink = assetDialogContent.querySelector('.ar-form-footer a');
                        if (cancelLink) {
                            const cancelButton = document.createElement('button');
                            cancelButton.type = 'button';
                            cancelButton.className = cancelLink.className;
                            cancelButton.dataset.arCloseAssetDialog = '';
                            cancelButton.textContent = cancelLink.textContent;
                            cancelLink.replaceWith(cancelButton);
                        }

                        const typeSelect = assetDialogContent.querySelector('#ar-asset-type');
                        const codePreview = assetDialogContent.querySelector('#ar-next-code');
                        const updateCodePreview = () => {
                            if (codePreview) codePreview.textContent = typeSelect?.selectedOptions[0]?.dataset.nextCode || '--';
                        };
                        typeSelect?.addEventListener('change', updateCodePreview);
                        updateCodePreview();
                        assetDialog.showModal();
                    } catch (error) {
                        console.error(error);
                        window.location.assign(addAssetLink.href);
                    }

                    return;
                }

                if (event.target.closest('[data-ar-close-asset-dialog]')) {
                    assetDialog.close();
                }
            });

            assetDialog.addEventListener('click', (event) => {
                if (event.target === assetDialog) assetDialog.close();
            });

            assetDialog.addEventListener('submit', async (event) => {
                const assetForm = event.target;
                if (!(assetForm instanceof HTMLFormElement) || !assetForm.matches('.ar-asset-form')) return;

                event.preventDefault();
                assetDialogFeedback.hidden = true;
                assetDialogFeedback.replaceChildren();
                assetForm.setAttribute('aria-busy', 'true');

                try {
                    const response = await fetch(assetForm.action, {
                        method: assetForm.method.toUpperCase(),
                        body: new FormData(assetForm),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (response.status === 422) {
                        const payload = await response.json();
                        const errors = Object.values(payload.errors || {}).flat();
                        if (errors.length === 0) throw new Error('Asset validation failed without field errors.');

                        const list = document.createElement('ul');
                        errors.forEach((message) => {
                            const item = document.createElement('li');
                            item.textContent = message;
                            list.append(item);
                        });
                        assetDialogFeedback.append(list);
                        assetDialogFeedback.hidden = false;
                        return;
                    }

                    if (!response.ok) throw new Error(`Asset save failed: ${response.status}`);
                    if (response.redirected) {
                        window.location.assign(response.url);
                        return;
                    }

                    throw new Error('Asset save response did not redirect to the saved asset.');
                } catch (error) {
                    console.error(error);
                    assetDialogFeedback.textContent = @js(__('asset-register::asset-register.registry.asset_save_error'));
                    assetDialogFeedback.hidden = false;
                } finally {
                    assetForm.removeAttribute('aria-busy');
                }
            });
        })();
    </script>
@endif
@include('asset-register::asset-register.styles')
@endsection
