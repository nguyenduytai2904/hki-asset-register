<div class="ar-registry-results" id="ar-registry-results" data-hk-table-region data-hk-current-page="{{ $assets->currentPage() }}" aria-live="polite">
    <section class="ar-card ar-registry-table">
        <div class="ar-table-wrap">
            <table>
                <thead>
                    <tr>
                        <x-hk.sortable-th
                            field="asset"
                            :label="__('asset-register::asset-register.registry.asset')"
                            :sort="$filters['sort']"
                            :direction="$filters['direction']"
                            :has-explicit="$filters['has_explicit_sort']"
                        />
                        <th>{{ __('asset-register::asset-register.registry.type') }}</th>
                        <th>{{ __('asset-register::asset-register.registry.keeper') }}</th>
                        <th>{{ __('asset-register::asset-register.registry.location') }}</th>
                        <th>{{ __('asset-register::asset-register.registry.condition') }}</th>
                        <x-hk.sortable-th
                            field="status"
                            :label="__('asset-register::asset-register.registry.status')"
                            :sort="$filters['sort']"
                            :direction="$filters['direction']"
                            :has-explicit="$filters['has_explicit_sort']"
                        />
                        <x-hk.sortable-th
                            field="warranty"
                            :label="__('asset-register::asset-register.registry.warranty')"
                            :sort="$filters['sort']"
                            :direction="$filters['direction']"
                            :has-explicit="$filters['has_explicit_sort']"
                        />
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
                                    {{ hk_date_only($asset->warranty_end_date) }}
                                    @if ($asset->status !== 'disposed')
                                        @php($warrantyEndDate = \Carbon\Carbon::parse($asset->warranty_end_date->toDateString(), $warrantyToday->timezone))
                                        @if ($warrantyEndDate->lt($warrantyToday))
                                            <small class="ar-warranty-expired">{{ __('asset-register::asset-register.registry.expired') }}</small>
                                        @elseif ($warrantyEndDate->isSameDay($warrantyToday))
                                            <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.warranty_today') }}</small>
                                        @elseif ($warrantyEndDate->lte($warrantyToday->copy()->addDays(30)))
                                            <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.days_remaining', ['count' => \Hki\AssetRegister\Support\AssetWarranty::daysUntil($warrantyEndDate, $warrantyToday)]) }}</small>
                                        @endif
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

    <x-hk.table-pagination
        :paginator="$assets"
        :unit="__('asset-register::asset-register.registry.asset_unit')"
    />
</div>
