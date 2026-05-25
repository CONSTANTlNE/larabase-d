@if($error)
    <div class="p-4">
        <div class="bg-red-500/10 border border-red-500/30 rounded-lg p-4">
            <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4 text-red-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-red-400 text-xs font-semibold">Query Error</span>
                <span class="text-gray-600 text-xs ml-auto">{{ $duration_ms }}ms</span>
            </div>
            <p class="text-red-300 text-xs font-mono mt-1">{{ $error }}</p>
        </div>
    </div>
@elseif(empty($rows) && empty($columns))
    <div class="p-4 text-xs text-gray-500">
        Query executed in {{ $duration_ms }}ms. {{ $affected > 0 ? $affected . ' row(s) affected.' : 'No results.' }}
    </div>
@else
    <div class="border-b border-gray-800 px-4 py-1.5 text-xs text-gray-500 bg-gray-900/50 flex items-center gap-3">
        <span>{{ count($rows) }} row{{ count($rows) !== 1 ? 's' : '' }}</span>
        <span>&middot; {{ $duration_ms }}ms</span>
    </div>
    <div class="overflow-auto">
        <table class="results-table text-xs text-left">
            <thead>
                <tr class="border-b border-gray-800">
                    @foreach($columns as $col)
                        <th class="px-3 py-2.5 text-gray-400 font-medium whitespace-nowrap border-r border-gray-800 last:border-r-0">
                            {{ $col }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr class="border-b border-gray-800/50 hover:bg-gray-800 transition-colors">
                        @foreach($row as $value)
                            <td class="px-3 py-2 whitespace-nowrap max-w-xs truncate border-r border-gray-800/30 last:border-r-0 text-gray-300">
                                @if(is_null($value))
                                    <span class="text-gray-600 italic">NULL</span>
                                @elseif(is_bool($value))
                                    <span class="{{ $value ? 'text-green-400' : 'text-red-400' }}">{{ $value ? 'true' : 'false' }}</span>
                                @else
                                    {{ Str::limit((string) $value, 100) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
