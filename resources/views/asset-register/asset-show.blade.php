@extends('layouts.app')

@section('title', $asset->code . ' · ' . $asset->name)

@section('content')
@php
    $pendingHandover = $asset->handovers->first(fn ($handover) => $handover->status === 'pending' && $handover->closed_at === null);
    $statusKey = $pendingHandover ? 'pending' : ($asset->status === 'pending_confirmation' ? 'pending' : $asset->status);
    $statusOptions = match ($asset->status) {
        'available', 'in_use' => ['in_repair', 'broken', 'lost'],
        'in_repair' => ['available', 'broken'],
        'broken' => ['available', 'in_repair'],
        'lost' => ['available'],
        default => [],
    };
    $pendingHandoverHistory = $pendingHandover ? $asset->histories->first(fn ($history) => $history->action === 'handed_over') : null;
@endphp
<main class="ar-page ar-detail-page">
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
            <h1>{{ $asset->code }} · {{ $asset->name }}</h1>
            <p>{{ $asset->type?->name ?: '--' }} · {{ $asset->location?->name ?: '--' }}</p>
        </div>
        <span @class(['ar-status', 'is-pending' => $statusKey === 'pending', 'is-warning' => $statusKey === 'in_repair', 'is-danger' => in_array($statusKey, ['broken', 'lost'], true), 'is-inactive' => $statusKey === 'disposed'])>{{ __('asset-register::asset-register.asset_status.' . $statusKey) }}</span>
        <div class="ar-detail-actions">
            @if ($canManage && $asset->status !== 'disposed')
                <a class="ar-button" href="{{ route('asset-register.assets.edit', $asset->id) }}"><span class="material-symbols-rounded" aria-hidden="true">edit</span>{{ __('asset-register::asset-register.asset_detail.edit') }}</a>
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
                    <button class="ar-button ar-button-danger" type="button" data-ar-open-action-dialog="dispose" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-purchase-date="{{ $asset->purchase_date?->format('Y-m-d') }}">{{ __('asset-register::asset-register.actions.dispose') }}</button>
                @endif
            @elseif ($isKeeperView && $asset->status !== 'disposed')
                @if ($pendingHandover)
                    <button class="ar-button ar-button-dark" type="button" data-ar-open-action-dialog="confirm" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" data-condition="{{ $pendingHandover->condition_on_handover ? __('asset-register::asset-register.condition.' . $pendingHandover->condition_on_handover) : '—' }}" data-assigned-by="{{ $pendingHandoverHistory?->user?->name }}" data-assigned-at="{{ $pendingHandover->handed_over_at?->format('d/m/Y H:i') }}">{{ __('asset-register::asset-register.mine.confirm_received') }}</button>
                @endif
                <button class="ar-button" type="button" data-ar-open-action-dialog="issue" data-code="{{ $asset->code }}" data-name="{{ $asset->name }}" @if ($pendingHandover) data-issue-type="incorrect" @endif>{{ $pendingHandover ? __('asset-register::asset-register.mine.report_incorrect') : __('asset-register::asset-register.mine.report_issue') }}</button>
            @endif
        </div>
    </header>

    @if ($asset->status === 'disposed')
        <div class="ar-disposal-banner">
            <span class="material-symbols-rounded" aria-hidden="true">inventory_2</span>
            {{ __('asset-register::asset-register.asset_detail.disposed_notice', [
                'date' => $asset->disposed_at?->format('d/m/Y') ?? '--',
                'method' => $asset->disposal_method ?: '--',
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
                    <div><dt>{{ __('asset-register::asset-register.asset_form.purchase_date') }}</dt><dd>{{ $asset->purchase_date?->format('d/m/Y') ?? '--' }}</dd></div>
                    <div>
                        <dt>{{ __('asset-register::asset-register.asset_form.warranty_end_date') }}</dt>
                        <dd>{{ $asset->warranty_end_date?->format('d/m/Y') ?? '--' }}
                            @if ($asset->status !== 'disposed' && $asset->warranty_end_date)
                                @if ($asset->warranty_end_date->lt($warrantyToday))
                                    <small class="ar-warranty-expired">{{ __('asset-register::asset-register.registry.expired') }}</small>
                                @elseif ($asset->warranty_end_date->isSameDay($warrantyToday))
                                    <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.warranty_today') }}</small>
                                @elseif ($asset->warranty_end_date->lte($warrantyToday->copy()->addDays(30)))
                                    <small class="ar-warranty-warning">{{ __('asset-register::asset-register.registry.days_remaining', ['count' => $warrantyToday->diffInDays($asset->warranty_end_date)]) }}</small>
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
                @forelse ($asset->histories as $history)
                    @php($historyData = json_decode((string) $history->description, true))
                    <div class="ar-history-row">
                        <span class="ar-history-icon material-symbols-rounded" aria-hidden="true">{{ $history->action === 'added' ? 'add' : 'history' }}</span>
                        <div>
                            <strong>{{ __('asset-register::asset-register.history_actions.' . $history->action) }}</strong>
                            <small>{{ $history->created_at?->format('d/m/Y H:i') }} · {{ $history->user?->name ?: __('asset-register::asset-register.asset_detail.system_actor') }}</small>
                            @if ($history->action === 'updated' && is_array($historyData))
                                <ul class="ar-history-changes">
                                    @foreach ($historyData as $field => $change)
                                        <li>{{ __('asset-register::asset-register.asset_fields.' . $field) }}: {{ $change['old'] ?? '--' }} → {{ $change['new'] ?? '--' }}</li>
                                    @endforeach
                                </ul>
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
            </section>
        </div>

        <aside class="ar-detail-side">
            <section class="ar-card ar-detail-card">
                <h2>{{ $isKeeperView ? __('asset-register::asset-register.asset_detail.you_are_keeper') : __('asset-register::asset-register.registry.keeper') }}</h2>
                @if ($asset->keeper)
                    <p><span class="ar-avatar">{{ mb_substr($asset->keeper->name, 0, 1) }}</span><span @class(['ar-inactive-keeper' => $asset->keeper_is_inactive])>{{ $asset->keeper->name }}@if ($asset->keeper_is_inactive) {{ __('asset-register::asset-register.registry.former_member') }}@endif</span></p>
                @else
                    <p class="ar-muted">{{ __('asset-register::asset-register.asset_detail.no_keeper') }}</p>
                @endif
                @if ($pendingHandover)
                    <div class="ar-pending-note">
                        {{ __('asset-register::asset-register.asset_detail.pending_since', ['date' => $pendingHandover->handed_over_at?->format('d/m/Y H:i') ?? '--']) }}
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
                            <small>{{ __('asset-register::asset-register.asset_detail.handover_at', ['date' => $handover->handed_over_at?->format('d/m/Y H:i') ?? '--']) }} · {{ __('asset-register::asset-register.handover_status.' . $handover->status) }}</small>
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
</main>
@include('asset-register::asset-register.action-dialogs')
@endsection
