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

    <header class="ar-heading ar-page-heading">
        <div>
            <h1>{{ __('asset-register::asset-register.mine.title') }}</h1>
            <p>{{ __('asset-register::asset-register.mine.description') }}</p>
        </div>
    </header>

    <section class="ar-card ar-empty" role="status">
        <p>{{ __('asset-register::asset-register.mine.not_available_yet') }}</p>
    </section>
</main>
@endsection
