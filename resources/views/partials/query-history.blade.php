<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-white">Query History</h3>
        <span class="text-xs text-gray-500">Last 50</span>
    </div>

    @if($history->isEmpty())
        <p class="text-sm text-gray-500">No queries yet.</p>
    @else
        <div class="space-y-2">
            @foreach($history as $item)
                <div class="bg-gray-900 border border-gray-800 rounded-lg p-3 group cursor-pointer hover:border-gray-700 transition-colors"
                    data-restore-sql="{{ $item->sql }}">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-400">
                            {{ $item->executed_at->diffForHumans() }}
                        </span>
                        <div class="flex items-center gap-2">
                            @if($item->error)
                                <span class="text-xs text-red-400 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    error
                                </span>
                            @else
                                <span class="text-xs text-green-400">{{ $item->duration_ms }}ms</span>
                            @endif
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 font-mono truncate">{{ Str::limit($item->sql, 100) }}</p>
                </div>
            @endforeach
        </div>
    @endif
</div>
