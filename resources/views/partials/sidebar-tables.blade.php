@php
    $grouped = collect($tables)->groupBy('schema');
    $multiSchema = $grouped->count() > 1;
@endphp

@if($grouped->isEmpty())
    <div class="px-4 py-3 text-xs text-gray-500">No tables found.</div>
@else
    <ul class="py-1">
        @foreach($grouped as $schema => $schemaTables)
            @if($multiSchema)
                <li class="px-4 pt-3 pb-1 text-xs text-gray-600 font-semibold uppercase tracking-wider select-none">
                    {{ $schema }}
                </li>
            @endif
            @foreach($schemaTables as $table)
                @php $qualifiedName = $table['schema'] . '.' . $table['name']; @endphp
                <li class="group">
                    <div class="flex items-center hover:bg-gray-800 transition-colors">
                        {{-- Table select button --}}
                        <button
                            type="button"
                            class="table-item flex-1 min-w-0 text-left px-4 py-2 text-sm text-gray-300 hover:text-white flex items-center gap-2"
                            data-table="{{ $qualifiedName }}"
                            onclick="selectTable('{{ $qualifiedName }}')"
                        >
                            <svg class="w-3.5 h-3.5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 4v16M14 4v16"/>
                            </svg>
                            <span class="truncate">{{ $table['name'] }}</span>
                        </button>

                        {{-- Hover action icons --}}
                        <div class="flex items-center gap-0.5 pr-2 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                            {{-- Clear all records (TRUNCATE) --}}
                            <button
                                type="button"
                                title="Clear all records"
                                class="p-1 rounded text-gray-600 hover:text-amber-400 hover:bg-amber-500/10 transition-colors"
                                data-table="{{ $qualifiedName }}"
                                onclick="event.stopPropagation(); openTruncateModal(this)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </button>
                            {{-- Drop table --}}
                            <button
                                type="button"
                                title="Drop table"
                                class="p-1 rounded text-gray-600 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                data-table="{{ $qualifiedName }}"
                                onclick="event.stopPropagation(); openDropTableModal(this)">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </li>
            @endforeach
        @endforeach
    </ul>
@endif
