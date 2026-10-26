@extends('layouts.app')

@section('title', $asset['code'] . ' · ' . $asset['name'])

@section('content')

<main class="ar-page ar-detail-page">
    <nav class="ar-tabs" aria-label="{{ __('asset-register::asset-register.name') }}">
        @foreach ($tabs as $tab)
            <a href="{{ $tab['url'] }}" @class(['ar-tab', 'is-active' => $tab['key'] === 'mine'])>
                <span class="material-symbols-rounded" aria-hidden="true">{{ $tab['icon'] }}</span>
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="ar-detail-breadcrumb">
        <a href="{{ route('asset-register.mine') }}">{{ __('asset-register::asset-register.tabs.mine') }}</a>
        <span>/</span>{{ $asset['code'] }}
    </div>
    <header class="ar-detail-header">
        <a class="ar-back-link" href="{{ route('asset-register.mine') }}" aria-label="{{ __('asset-register::asset-register.asset_detail.back') }}">
            <span class="material-symbols-rounded" aria-hidden="true">arrow_back</span>
        </a>
        <span class="ar-device-icon ar-detail-icon"><span class="material-symbols-rounded" aria-hidden="true">{{ $asset['icon'] }}</span></span>
        <div class="ar-detail-title">
            <h1>{{ $asset['code'] }} · {{ $asset['name'] }}</h1>
            <p>{{ $asset['type'] }} · {{ $asset['location'] }}</p>
        </div>
        <span @class(['ar-status', 'is-pending' => $asset['status_key'] === 'pending'])>{{ __('asset-register::asset-register.asset_status.' . $asset['status_key']) }}</span>
        <div class="ar-detail-actions">
            <button class="ar-button" type="button" data-ar-open-action-dialog="issue" data-code="{{ $asset['code'] }}" data-name="{{ $asset['name'] }}" @if ($asset['status_key'] === 'pending') data-issue-type="incorrect" @endif>
                <span class="material-symbols-rounded" aria-hidden="true">report</span>{{ $asset['status_key'] === 'pending' ? __('asset-register::asset-register.mine.report_incorrect') : __('asset-register::asset-register.mine.report_issue') }}
            </button>
            @if ($asset['status_key'] === 'pending')
                <button class="ar-button ar-button-dark" type="button" data-ar-open-action-dialog="confirm" data-code="{{ $asset['code'] }}" data-name="{{ $asset['name'] }}" data-condition="{{ __('asset-register::asset-register.condition.' . $asset['condition_key']) }}" data-assigned-by="{{ $asset['assigned_by'] }}" data-assigned-at="{{ $asset['assigned_at'] }}">
                    <span class="material-symbols-rounded" aria-hidden="true">task_alt</span>{{ __('asset-register::asset-register.mine.confirm_received') }}
                </button>
            @endif
        </div>
    </header>

    <div class="ar-detail-grid">
        <div class="ar-detail-main">
            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.general') }}</h2>
                <dl class="ar-detail-fields">
                    <div><dt>{{ __('asset-register::asset-register.asset_form.code') }}</dt><dd><strong>{{ $asset['code'] }}</strong></dd></div>
                    <div><dt>{{ __('asset-register::asset-register.settings.type') }}</dt><dd>{{ $asset['type'] }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.brand') }}</dt><dd>{{ $asset['brand'] ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.model') }}</dt><dd>{{ $asset['model'] }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.serial') }}</dt><dd>{{ $asset['serial'] }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.condition') }}</dt><dd>{{ __('asset-register::asset-register.condition.' . $asset['condition_key']) }}</dd></div>
                    <div class="ar-detail-field-wide"><dt>{{ __('asset-register::asset-register.asset_form.configuration') }}</dt><dd>{{ $asset['configuration'] ?: '--' }}</dd></div>
                </dl>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_form.purchase_warranty') }}</h2>
                <dl class="ar-detail-fields">
                    <div><dt>{{ __('asset-register::asset-register.asset_form.purchase_date') }}</dt><dd>{{ $asset['purchase_date'] ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.warranty_end_date') }}</dt><dd>{{ $asset['warranty'] }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.supplier') }}</dt><dd>{{ $asset['supplier'] ?: '--' }}</dd></div>
                    <div><dt>{{ __('asset-register::asset-register.asset_form.receipt_number') }}</dt><dd>{{ $asset['receipt_number'] ?: '--' }}</dd></div>
                </dl>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.history') }} ({{ __('asset-register::asset-register.tabs.mine') }})</h2>
                <div class="ar-history-row">
                    <span class="ar-history-icon material-symbols-rounded" aria-hidden="true">assignment_ind</span>
                    <div>
                        <strong>{{ __('asset-register::asset-register.asset_detail.handed_over_to', ['name' => $asset['keeper']]) }}</strong>
                        <small>{{ $asset['assigned_at'] }} · {{ $asset['assigned_by'] }} · {{ __('asset-register::asset-register.asset_detail.condition_at_handover', ['condition' => __('asset-register::asset-register.condition.' . $asset['condition_key'])]) }}</small>
                    </div>
                </div>
            </section>
        </div>

        <aside class="ar-detail-side">
            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.you_are_keeper') }}</h2>
                <p><span class="ar-avatar">{{ mb_substr($asset['keeper'], 0, 1) }}</span>{{ $asset['keeper'] }}</p>
                @if ($asset['status_key'] === 'pending')
                    <div class="ar-pending-note">
                        <span class="material-symbols-rounded" aria-hidden="true">schedule</span>
                        {{ __('asset-register::asset-register.asset_detail.pending_since', ['date' => $asset['assigned_at']]) }}
                    </div>
                @endif
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.registry.location') }}</h2>
                <p><span class="material-symbols-rounded ar-type-icon" aria-hidden="true">location_on</span>{{ $asset['location'] }}</p>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_detail.handovers') }} ({{ __('asset-register::asset-register.tabs.mine') }})</h2>
                <div class="ar-handover-row">
                    <span class="material-symbols-rounded ar-type-icon" aria-hidden="true">assignment_ind</span>
                    <span>{{ $asset['keeper'] }}
                        <small>{{ __('asset-register::asset-register.asset_detail.handover_at', ['date' => $asset['assigned_at']]) }} · {{ __('asset-register::asset-register.handover_status.' . $asset['handover_status']) }}</small>
                        <small>{{ __('asset-register::asset-register.asset_form.condition') }}: {{ __('asset-register::asset-register.condition.' . $asset['condition_key']) }}</small>
                    </span>
                </div>
            </section>

            <section class="ar-card ar-detail-card">
                <h2>{{ __('asset-register::asset-register.asset_form.note') }}</h2>
                <p>{{ $asset['note'] ?: __('asset-register::asset-register.asset_detail.no_note') }}</p>
            </section>
        </aside>
    </div>
</main>
@include('asset-register::asset-register.action-dialogs')
@endsection
