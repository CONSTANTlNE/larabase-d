@extends('layouts.app')
@section('title', 'Connections — LaraBase-D')

@section('content')
<div class="min-h-screen bg-gray-950">
    {{-- Top bar --}}
    <header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between">
        <h1 class="text-lg font-semibold text-white">LaraBase-D</h1>
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-400">{{ auth()->user()->email }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="text-sm text-gray-400 hover:text-gray-200 transition-colors">Sign out</button>
            </form>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-6 py-8">
        {{-- Flash messages --}}
        @if (session('success'))
            <div class="mb-6 text-green-400 text-sm bg-green-500/10 border border-green-500/20 rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-6 text-red-400 text-sm bg-red-500/10 border border-red-500/20 rounded-lg px-4 py-3">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-white">Connections</h2>
            <button
                hx-get="{{ route('connections.index') }}?form=1"
                hx-target="#new-connection-form"
                hx-swap="innerHTML"
                class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors"
            >
                + New Connection
            </button>
        </div>

        {{-- Inline new connection form --}}
        <div id="new-connection-form">
            @if (request('form'))
                @include('partials.connection-form')
            @endif
        </div>

        {{-- Connection cards --}}
        @if ($connections->isEmpty())
            <div class="text-center py-20 text-gray-500">
                <svg class="mx-auto w-12 h-12 mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4s8 1.79 8 4"/>
                </svg>
                <p class="text-sm">No connections yet. Add one above.</p>
            </div>
        @else
            <div class="grid gap-4">
                @foreach ($connections as $connection)
                    <div class="bg-gray-900 border border-gray-800 rounded-xl p-5 flex items-center justify-between group" id="connection-{{ $connection->id }}">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 border border-blue-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4"/>
                                </svg>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ $connection->name }}</p>
                                <p class="text-sm text-gray-400">{{ $connection->host }}:{{ $connection->port }} / {{ $connection->database }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            {{-- Copy connection string --}}
                            <button
                                type="button"
                                title="Copy connection string"
                                onclick="navigator.clipboard.writeText('postgresql://{{ $connection->username }}@{{ $connection->host }}:{{ $connection->port }}/{{ $connection->database }}')"
                                class="p-2 text-gray-500 hover:text-gray-300 rounded-lg hover:bg-gray-800 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                </svg>
                            </button>

                            {{-- Test connection --}}
                            <button
                                type="button"
                                id="test-btn-{{ $connection->id }}"
                                hx-post="{{ route('connections.test', $connection) }}"
                                hx-target="#test-result-{{ $connection->id }}"
                                hx-swap="innerHTML"
                                hx-indicator="#test-btn-{{ $connection->id }}"
                                class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 hover:border-gray-600 px-3 py-1.5 rounded-lg transition-colors"
                            >
                                Test
                            </button>
                            <span id="test-result-{{ $connection->id }}" class="text-xs min-w-0"></span>

                            {{-- Connect --}}
                            <a
                                href="{{ route('connections.connect', $connection) }}"
                                class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-4 py-1.5 rounded-lg transition-colors font-medium"
                            >
                                Connect
                            </a>

                            {{-- Delete --}}
                            <button
                                type="button"
                                hx-delete="{{ route('connections.destroy', $connection) }}"
                                hx-target="#connection-{{ $connection->id }}"
                                hx-swap="outerHTML"
                                hx-confirm="Delete connection '{{ $connection->name }}'?"
                                hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                                class="p-2 text-gray-500 hover:text-red-400 rounded-lg hover:bg-gray-800 transition-colors"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
    // Show test result inline
    document.body.addEventListener('htmx:afterRequest', function(evt) {
        const triggerBtn = evt.detail.elt;
        if (!triggerBtn.id || !triggerBtn.id.startsWith('test-btn-')) return;
        const connId = triggerBtn.id.replace('test-btn-', '');
        const resultEl = document.getElementById('test-result-' + connId);
        if (!resultEl || !evt.detail.xhr) return;

        try {
            const json = JSON.parse(evt.detail.xhr.responseText);
            resultEl.textContent = json.message;
            resultEl.className = 'text-xs ' + (json.success ? 'text-green-400' : 'text-red-400');
            setTimeout(() => { resultEl.textContent = ''; resultEl.className = 'text-xs'; }, 4000);
        } catch(e) {}
    });
</script>
@endpush
@endsection
