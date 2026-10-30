<header class="ar-heading ar-page-heading">
    <div>
        <h1>{{ __('asset-register::asset-register.mine.title') }}</h1>
        <p>{{ __('asset-register::asset-register.mine.description') }}</p>
    </div>
</header>

<section class="ar-view-content ar-mine-content">
    @if ($pendingAssets->isNotEmpty())
        <section class="ar-pending-card" aria-labelledby="ar-pending-heading">
            <h2 id="ar-pending-heading">
                <span class="material-symbols-rounded" aria-hidden="true">inventory</span>
                {{ __('asset-register::asset-register.mine.pending', ['count' => $pendingCount]) }}
            </h2>
            @foreach ($pendingAssets as $asset)
                @php
                    $handover = $asset->handovers->first();
                    $handoverHistory = $asset->histories->first();
                @endphp
                <div class="ar-pending-item">
                    <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span></span>
                    <div class="ar-pending-asset">
                        <a href="{{ route('asset-register.assets.show', $asset->id) }}"><strong>{{ $asset->code }}</strong> · {{ $asset->name }}</a>
                        <small>{{ $asset->model ?: '--' }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset->serial ?: '--' }}</small>
                    </div>
                    <p>
                        {{ __('asset-register::asset-register.mine.handed_over_by', [
                            'name' => $handoverHistory?->user?->name ?: '--',
                            'date' => hk_datetime($handover?->handed_over_at) ?: '--',
                        ]) }}
                        @if ($handover?->condition_on_handover)
                            · {{ __('asset-register::asset-register.mine.condition_at_handover', ['condition' => __('asset-register::asset-register.condition.' . $handover->condition_on_handover)]) }}
                        @endif
                    </p>
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
                    @forelse ($assets as $asset)
                        @php
                            $handover = $asset->handovers->first();
                            $isPending = $handover?->status === 'pending';
                            $statusKey = $isPending ? 'pending' : $asset->status;
                            $warrantyEndDate = $asset->warranty_end_date
                                ? \Carbon\Carbon::parse($asset->warranty_end_date->toDateString(), $warrantyToday->timezone)
                                : null;
                        @endphp
                        <tr>
                            <td class="ar-asset-cell">
                                <span class="ar-device-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span></span>
                                <span>
                                    <a href="{{ route('asset-register.assets.show', $asset->id) }}"><strong>{{ $asset->code }}</strong> · {{ $asset->name }}</a>
                                    <small>{{ $asset->model ?: '--' }} · {{ __('asset-register::asset-register.registry.serial') }} {{ $asset->serial ?: '--' }}</small>
                                </span>
                            </td>
                            <td>{{ __('asset-register::asset-register.condition.' . $asset->condition) }}</td>
                            <td>{{ hk_datetime($handover?->handed_over_at) ?: '--' }}</td>
                            <td><span @class(['ar-status', 'is-pending' => $isPending])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span></td>
                            <td>
                                {{ hk_date_only($asset->warranty_end_date) ?: '--' }}
                                @if ($asset->status !== 'disposed' && $warrantyEndDate)
                                    @if ($warrantyEndDate->lt($warrantyToday))
                                        <small class="ar-warranty-expired">{{ __('asset-register::asset-register.registry.expired') }}</small>
                                    @elseif ($warrantyEndDate->isSameDay($warrantyToday))
                                        <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.warranty_today') }}</small>
                                    @elseif ($warrantyEndDate->lte($warrantyToday->copy()->addDays(30)))
                                        <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.days_remaining', ['count' => \Hki\AssetRegister\Support\AssetWarranty::daysUntil($warrantyEndDate, $warrantyToday)]) }}</small>
                                    @endif
                                @endif
                            </td>
                            <td><a class="ar-row-arrow" href="{{ route('asset-register.assets.show', $asset->id) }}" aria-label="{{ __('asset-register::asset-register.registry.details') }}"><span class="material-symbols-rounded" aria-hidden="true">chevron_right</span></a></td>
                        </tr>
                    @empty
                        <tr><td class="ar-empty" colspan="6">{{ __('asset-register::asset-register.placeholders.mine') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-hk.table-pagination
            :paginator="$assets"
            :unit="__('asset-register::asset-register.registry.asset_unit')"
        />
    </section>
</section>
