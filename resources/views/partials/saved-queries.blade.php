<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-white">Saved Queries</h3>
        <button
            onclick="activateTab('query')"
            class="text-xs text-blue-400 hover:text-blue-300 transition-colors"
        >
            Open Editor →
        </button>
    </div>

    @if($savedQueries->isEmpty())
        <p class="text-sm text-gray-500">No saved queries yet.</p>
    @else
        <div class="space-y-2">
            @foreach($savedQueries as $query)
                <div class="bg-gray-900 border border-gray-800 rounded-lg p-3 group" id="saved-query-{{ $query->id }}">
                    <div class="flex items-center justify-between mb-1.5">
                        <button
                            type="button"
                            data-restore-sql="{{ $query->sql }}"
                            class="text-sm font-medium text-white hover:text-blue-400 transition-colors text-left"
                        >
                            {{ $query->name }}
                        </button>
                        <button
                            hx-delete="{{ route('browser.saved-queries.destroy', $query) }}"
                            hx-target="#saved-query-{{ $query->id }}"
                            hx-swap="outerHTML"
                            hx-confirm="Delete '{{ $query->name }}'?"
                            hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                            class="text-gray-600 hover:text-red-400 transition-colors opacity-0 group-hover:opacity-100"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 font-mono truncate">{{ Str::limit($query->sql, 80) }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
