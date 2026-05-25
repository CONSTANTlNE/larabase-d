@php
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    $columns = !empty($rows) ? array_keys($rows[0]) : [];
    $hasPk = !empty($pkColumns);
    // JSON_HEX_TAG + JSON_HEX_AMP prevent HTML injection; {{ }} will htmlspecialchars the quotes,
    // and the HTML parser decodes them back before JS reads dataset — JSON.parse then works correctly.
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
@endphp

<div class="flex flex-col">
    {{-- Sticky header --}}
    <div class="sticky top-0 z-10 flex items-center justify-between px-4 py-2 bg-gray-900 border-b border-gray-800">
        <span class="text-xs text-gray-400">
            {{ number_format($total) }} row{{ $total !== 1 ? 's' : '' }}
            @if($totalPages > 1)
                &middot; Page {{ $page }} of {{ $totalPages }}
            @endif
        </span>
        <div class="flex items-center gap-2">
            @if($page > 1)
                <button
                    class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-2 py-1 rounded transition-colors"
                    onclick="loadTableData('{{ $table }}', {{ $page - 1 }})"
                >← Prev</button>
            @endif
            @if($page < $totalPages)
                <button
                    class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-2 py-1 rounded transition-colors"
                    onclick="loadTableData('{{ $table }}', {{ $page + 1 }})"
                >Next →</button>
            @endif
        </div>
    </div>

    {{-- Data grid --}}
    <div>
        @if(empty($rows))
            <div class="flex items-center justify-center h-32 text-gray-500 text-sm">No rows found.</div>
        @else
            <table class="results-table text-xs text-left">
                <thead>
                    <tr class="border-b border-gray-800">
                        @foreach($columns as $col)
                            <th class="px-3 py-2.5 text-gray-400 font-medium whitespace-nowrap border-r border-gray-800 last:border-r-0">
                                <button
                                    class="hover:text-gray-200 transition-colors flex items-center gap-1"
                                    data-table="{{ $table }}"
                                    data-col="{{ $col }}"
                                    onclick="loadSortedTable(this.dataset.table, this.dataset.col)"
                                >
                                    {{ $col }}
                                    @if($sortCol === $col)
                                        <span>{{ $sortDir === 'ASC' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        @if($hasPk)
                            <th class="px-3 py-2.5 text-gray-600 font-medium whitespace-nowrap w-16 text-center">
                                Actions
                            </th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $pkValues = [];
                            foreach ($pkColumns as $pkCol) {
                                $pkValues[$pkCol] = $row[$pkCol] ?? null;
                            }
                        @endphp
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/40 transition-colors group">
                            @foreach($row as $col => $value)
                                @php
                                    $cellStr = is_null($value) ? null
                                        : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
                                @endphp
                                <td
                                    class="px-3 py-2 whitespace-nowrap max-w-xs truncate border-r border-gray-800/30 last:border-r-0 text-gray-300 cursor-pointer hover:bg-gray-700/40"
                                    data-col="{{ $col }}"
                                    data-is-null="{{ is_null($value) ? '1' : '0' }}"
                                    data-val="{{ $cellStr ?? '' }}"
                                    onclick="openCellModal(this)"
                                    title="Click to expand"
                                >
                                    @if(is_null($value))
                                        <span class="text-gray-600 italic">NULL</span>
                                    @elseif(is_bool($value))
                                        <span class="{{ $value ? 'text-green-400' : 'text-red-400' }}">{{ $value ? 'true' : 'false' }}</span>
                                    @else
                                        {{ Str::limit((string) $value, 100) }}
                                    @endif
                                </td>
                            @endforeach
                            @if($hasPk)
                                <td class="px-2 py-1.5 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button
                                            type="button"
                                            class="p-1 rounded text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 transition-colors"
                                            title="Edit row"
                                            data-table="{{ $table }}"
                                            data-row="{{ json_encode($row, $jsonFlags) }}"
                                            data-pk-columns="{{ json_encode($pkColumns, $jsonFlags) }}"
                                            onclick="openEditModal(this)"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            class="p-1 rounded text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                            title="Delete row"
                                            data-table="{{ $table }}"
                                            data-pk="{{ json_encode($pkValues, $jsonFlags) }}"
                                            onclick="openDeleteModal(this)"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

<script>
    window._currentSortCol = @json($sortCol);
    window._currentSortDir = @json($sortDir);

    function loadSortedTable(table, col) {
        let dir = 'ASC';
        if (window._currentSortCol === col) {
            dir = window._currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        }
        htmx.ajax('GET', `/browser/tables/${encodeURIComponent(table)}?page=1&sort=${encodeURIComponent(col)}&dir=${dir}`, {
            target: '#main-content',
            swap: 'innerHTML',
        });
    }
</script>
