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
                <li>
                    <button
                        type="button"
                        class="table-item w-full text-left px-4 py-2 text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition-colors flex items-center gap-2"
                        data-table="{{ $qualifiedName }}"
                        onclick="selectTable('{{ $qualifiedName }}')"
                    >
                        <svg class="w-3.5 h-3.5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 4v16M14 4v16"/>
                        </svg>
                        <span class="truncate">{{ $table['name'] }}</span>
                    </button>
                </li>
            @endforeach
        @endforeach
    </ul>
@endif
