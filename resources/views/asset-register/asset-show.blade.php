@extends('layouts.app')

@section('title', $asset->code . ' · ' . $asset->name)

@section('content')
@php
    $pendingHandover = $asset->handovers->first(fn ($handover) => $handover->status === 'pending' && $handover->closed_at === null);
    $statusKey = $pendingHandover ? 'pending' : ($asset->status === 'pending_confirmation' ? 'pending' : $asset->status);
    $statusOptions = $allowedStatusTransitions;
@endphp
<main class="ar-page ar-detail-page" @if ($canManage) x-data="window.HK.assetRegister.assetFormDialog({ i18n: { saveError: @js(__('asset-register::asset-register.registry.asset_save_error')) } })" @endif>
    <nav class="ar-tabs" aria-label="{{ __('asset-register::asset-register.name') }}">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class(['ar-tab', 'is-active' => $activeTab === $tab['key']])>
                <span class="material-symbols-rounded" aria-hidden="true">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    @if (session('success'))
        <div class="ar-alert ar-alert-success" role="status">{{ session('success') }}</div>
    @endif

    <div class="ar-detail-breadcrumb">
        <a href="{{ $isKeeperView ? route('asset-register.mine') : route('asset-register.registry') }}">{{ $isKeeperView ? __('asset-register::asset-register.tabs.mine') : __('asset-register::asset-register.tabs.registry') }}</a>
        <span>/</span>{{ $asset->code }}
    </div>
    <header class="ar-detail-header">
        <a class="ar-back-link" href="{{ $isKeeperView ? route('asset-register.mine') : route('asset-register.registry') }}" aria-label="{{ __('asset-register::asset-register.asset_detail.back') }}">
            <span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
        </a>
        <span class="ar-device-icon ar-detail-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset->type?->displayIcon() ?: 'category' }}</span></span>
        <div class="ar-detail-title">
            <h1>
                {{ $asset->code }} · {{ $asset->name }}
                <span class="ar-detail-status-group">
                    @if ($latestStatusChange)
                        <span class="ar-status-reason" tabindex="0" aria-describedby="ar-latest-status-change">
                            <span @class(['ar-status', 'is-pending' => $statusKey === 'pending', 'is-warning' => $statusKey === 'in_repair', 'is-danger' => in_array($statusKey, ['broken', 'lost'], true), 'is-inactive' => $statusKey === 'disposed'])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span>
                            <span class="ar-status-reason-tooltip" id="ar-latest-status-change" role="tooltip" data-ar-latest-status-change>
                                <strong>{{ __('asset-register::asset-register.actions.latest_status_change') }}</strong>
                                <p>{{ __('asset-register::asset-register.asset_status.' . $latestStatusChange['from']) }} → {{ __('asset-register::asset-register.asset_status.' . $latestStatusChange['to']) }}</p>
                                <p><strong>{{ __('asset-register::asset-register.actions.reason') }}:</strong> {{ $latestStatusChange['reason'] }}</p>
                            </span>
                        </span>
                    @else
                        <span @class(['ar-status', 'is-pending' => $statusKey === 'pending', 'is-warning' => $statusKey === 'in_repair', 'is-danger' => in_array($statusKey, ['broken', 'lost'], true), 'is-inactive' => $statusKey === 'disposed'])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span>
                    @endif
                </span>
            </h1>
            <p>{{ $asset->type?->name ?: '--' }} · {{ $asset->location?->name ?: '--' }}</p>
        </div>
        <div class="ar-detail-actions">
            @if ($canManage && $asset->status !== 'disposed')
                <a class="ar-button ar-edit-asset" href="{{ route('asset-register.assets.edit', $asset->id) }}"><span class="material-symbols-rounded" aria-hidden="true">edit</span>{{ __('asset-register::asset-register.asset_detail.edit') }}</a>
                @if ($asset->status === 'available')
                    <button class="ar-button ar-button-dark" type="button" data-ar-open-action-dialog="handover" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-action-title="{{ __('asset-register::asset-register.actions.handover_title') }}"><span class="material-symbols-rounded" aria-hidden="true">assignment_ind</span>{{ __('asset-register::asset-register.actions.handover') }}</button>
                @elseif ($asset->status === 'in_use')
                    <button class="ar-button ar-button-dark" type="button" data-ar-open-action-dialog="handover" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-action-title="{{ __('asset-register::asset-register.actions.transfer_title') }}" data-current-keeper="{{ $asset->keeper?->name }}"><span class="material-symbols-rounded" aria-hidden="true">swap_horiz</span>{{ __('asset-register::asset-register.actions.transfer') }}</button>
                    <button class="ar-button" type="button" data-ar-open-action-dialog="return" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-recipient="{{ $asset->keeper?->name }}" data-pending="{{ $pendingHandover ? 'true' : 'false' }}">{{ __('asset-register::asset-register.actions.return') }}</button>
                @endif
                @if ($statusOptions !== [])
                    <button class="ar-button" type="button" data-ar-open-action-dialog="status" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-status-options="{{ implode(',', $statusOptions) }}" data-status-label="{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}" data-status-class="{{ $statusKey === 'pending' ? 'is-pending' : (in_array($statusKey, ['in_repair'], true) ? 'is-warning' : (in_array($statusKey, ['broken', 'lost'], true) ? 'is-danger' : '')) }}">{{ __('asset-register::asset-register.actions.change_status') }}</button>
                @endif
                @if ($asset->status === 'in_use')
                    <button class="ar-button ar-button-danger" type="button" disabled title="{{ __('asset-register::asset-register.actions.return_before_disposal') }}">{{ __('asset-register::asset-register.actions.dispose') }}</button>
                @elseif ($asset->status !== 'disposed')
                    <button class="ar-button ar-button-danger" type="button" data-ar-open-action-dialog="dispose" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-purchase-date="{{ $asset->purchase_date?->format('Y-m-d') }}" data-today="{{ $warrantyToday->toDateString() }}">{{ __('asset-register::asset-register.actions.dispose') }}</button>
                @endif
            @endif
        </div>
    </header>

    @if ($asset->status === 'disposed')
        <div class="ar-disposal-banner">
            <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
            {{ __('asset-register::asset-register.asset_detail.disposed_notice', [
                'date' => hk_date($asset->disposed_at) ?? '--',
                'method' => $asset->disposal_method
                    ? __('asset-register::asset-register.actions.method_' . $asset->disposal_method)
                    : '--',
                'basis' => $asset->disposal_basis ?: '--',
                'code' => $asset->code,
            ]) }}
        </div>
    @endif

    <div class="ar-detail-grid">
        <div class="ar-detail-main">
            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.general') }}</h2>
                <dl class="ar-detail-fields">
                    <div><dt>{{ __('asset-register::asset-register.asset_form.code') }}</dt><dd>{{ $asset->code }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.type') }}</dt><dd>{{ $asset->type?->name ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.brand') }}</dt><dd>{{ $asset->brand ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.model') }}</dt><dd>{{ $asset->model ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.serial') }}</dt><dd>{{ $asset->serial ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.condition') }}</dt><dd>{{ __('asset-register::asset-register.condition.' . $asset->condition) }}</dd></div>
                    <div class="ar-detail-field-wide"><dt>{{ __('asset-register::asset-register.asset_form.configuration') }}</dt><dd>{{ $asset->configuration ?: '--' }}</dd></div>
                </dl>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_form.purchase_warranty') }}</h2>
                <dl class="ar-detail-fields">
                    <div><dt>{{ __('asset-register::asset-register.asset_form.purchase_date') }}</dt><dd>{{ hk_date_only($asset->purchase_date) ?? '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.warranty_duration') }}</dt><dd>{{ $asset->warranty_duration_months ? __('asset-register::asset-register.asset_form.warranty_duration_value', ['count' => $asset->warranty_duration_months]) : '--' }}</dd></div>
                    <div>
                        <dt>{{ __('asset-register::asset-register.asset_form.warranty_end_date') }}</dt>
                        <dd>{{ hk_date_only($asset->warranty_end_date) ?? '--' }}
                            @if ($asset->status !== 'disposed' && $asset->warranty_end_date)
                                @php($warrantyEndDate = \Carbon\Carbon::parse($asset->warranty_end_date->toDateString(), $warrantyToday->timezone))
                                @if ($warrantyEndDate->lt($warrantyToday))
                                    <small class="ar-warranty-expired">{{ __('asset-register::asset-register.registry.expired') }}</small>
                                @elseif ($warrantyEndDate->isSameDay($warrantyToday))
                                    <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.warranty_today') }}</small>
                                @elseif ($warrantyEndDate->lte($warrantyToday->copy()->addDays(30)))
                                    <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.days_remaining', ['count' => \Hki\AssetRegister\Support\AssetWarranty::daysUntil($warrantyEndDate, $warrantyToday)]) }}</small>
                                @endif
                            @endif
                        </dd>
                    </div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.supplier') }}</dt><dd>{{ $asset->supplier ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.receipt_number') }}</dt><dd>{{ $asset->receipt_number ?: '--' }}</dd></div>
                </dl>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.history') }}@if ($isKeeperView) ({{ __('asset-register::asset-register.tabs.mine') }})@endif</h2>
                <div class="ar-history-list" role="region" aria-label="{{ __('asset-register::asset-register.asset_detail.history') }}" tabindex="0">
                    @forelse ($asset->histories as $history)
                        @php($historyData = json_decode((string) $history->description, true))
                        <div class="ar-history-row">
                            <span class="ar-history-icon material-symbols-rounded" aria-hidden="true">{{ $history->action === 'added' ? 'add' : 'history' }}</span>
                            <div>
                                <strong>{{ __('asset-register::asset-register.history_actions.' . $history->action) }}</strong>
                                <small>{{ hk_datetime($history->created_at) }} · {{ $history->user?->name ?: __('asset-register::asset-register.asset_detail.system_actor') }}</small>
                                @if ($history->action === 'updated' && is_array($historyData))
                                    <ul class="ar-history-changes">
                                        @foreach ($historyData as $field => $change)
                                            <li>{{ __('asset-register::asset-register.asset_fields.' . $field) }}: {{ $change['old'] ?? '--' }} → {{ $change['new'] ?? '--' }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($history->action === 'status_changed' && is_array($historyData) && isset($historyData['from'], $historyData['to'], $historyData['reason']))
                                    <small>{{ __('asset-register::asset-register.asset_status.' . $historyData['from']) }} → {{ __('asset-register::asset-register.asset_status.' . $historyData['to']) }}</small>
                                    <small class="ar-history-reason"><strong>{{ __('asset-register::asset-register.actions.reason') }}:</strong> {{ $historyData['reason'] }}</small>
                                @elseif ($history->action === 'disposed' && is_array($historyData) && isset($historyData['disposed_at'], $historyData['method'], $historyData['basis']))
                                    <small>{{ hk_date_only($historyData['disposed_at']) }} · {{ __('asset-register::asset-register.actions.method_' . $historyData['method']) }}</small>
                                    <small>{{ $historyData['basis'] }}</small>
                                @elseif ($historyData && isset($historyData['code']))
                                    <small>{{ $historyData['code'] }}</small>
                                @elseif ($history->description)
                                    <small>{{ $history->description }}</small>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="ar-empty">{{ __('asset-register::asset-register.asset_detail.no_history') }}</p>
                    @endforelse
                </div>
            </section>
        </div>

        <aside class="ar-detail-side">
            <section class="ar-card ar-detail-card">
                @if ($isKeeperView && $pendingHandover && (int) $pendingHandover->recipient_id === (int) auth()->id())
                    <h2>{{ __('asset-register::asset-register.mine.waiting_for_confirmation') }}</h2>
                    <p>{{ __('asset-register::asset-register.asset_detail.pending_since', ['date' => hk_datetime($pendingHandover->handed_over_at) ?? '--']) }}</p>
                @else
                    <h2>{{ $isKeeperView ? __('asset-register::asset-register.asset_detail.you_are_keeper') : __('asset-register::asset-register.registry.keeper') }}</h2>
                @endif
                @if (! ($isKeeperView && $pendingHandover && (int) $pendingHandover->recipient_id === (int) auth()->id()) && $asset->keeper)
                    <p><span class="ar-avatar">{{ mb_substr($asset->keeper->name, 0, 1) }}</span><span @class(['ar-inactive-keeper' => $asset->keeper_is_inactive])>{{ $asset->keeper->name }}@if ($asset->keeper_is_inactive) {{ __('asset-register::asset-register.registry.former_member') }}@endif</span></p>
                @elseif (! ($isKeeperView && $pendingHandover && (int) $pendingHandover->recipient_id === (int) auth()->id()))
                    <p class="ar-muted">{{ __('asset-register::asset-register.asset_detail.no_keeper') }}</p>
                @endif
                @if ($pendingHandover)
                    <div class="ar-pending-note">
                        {{ __('asset-register::asset-register.asset_detail.pending_since', ['date' => hk_datetime($pendingHandover->handed_over_at) ?? '--']) }}
                    </div>
                @endif
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.registry.location') }}</h2>
                <p><span class="material-symbols-rounded ar-type-icon" aria-hidden="true">location_on</span>{{ $asset->location?->name ?: '--' }}</p>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.handovers') }}@if ($isKeeperView) ({{ __('asset-register::asset-register.tabs.mine') }})@endif</h2>
                @forelse ($asset->handovers as $handover)
                    <div class="ar-handover-row">
                        <span class="material-symbols-rounded ar-type-icon" aria-hidden="true">assignment_ind</span>
                        <span>{{ $handover->recipient?->name ?: '--' }}
                            <small>{{ __('asset-register::asset-register.asset_detail.handover_at', ['date' => hk_datetime($handover->handed_over_at) ?? '--']) }} · {{ __('asset-register::asset-register.handover_status.' . $handover->status) }}</small>
                            @if ($handover->condition_on_handover)
                                <small>{{ __('asset-register::asset-register.asset_form.condition') }}: {{ __('asset-register::asset-register.condition.' . $handover->condition_on_handover) }}</small>
                            @endif
                        </span>
                    </div>
                @empty
                    <p class="ar-muted">{{ __('asset-register::asset-register.asset_detail.no_handovers') }}</p>
                @endforelse
            </section>

            @if (! $isKeeperView)
                <section class="ar-card ar-detail-card">
                    <h2>{{ __('asset-register::asset-register.asset_form.note') }}</h2>
                    <p>{{ $asset->note ?: __('asset-register::asset-register.asset_detail.no_note') }}</p>
                </section>
            @endif
        </aside>
    </div>
    @if ($canManage)
        @include('asset-register::asset-register.partials.asset-form-dialog')
    @endif
</main>
@if ($canManage)
    @include('asset-register::asset-register.action-dialogs')
@endif
@endsection
