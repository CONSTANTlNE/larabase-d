@php
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    $columns = !empty($rows) ? array_keys($rows[0]) : array_keys($colTypes);
    $hasPk = !empty($pkColumns);
    $hasSearch = $searchCol && $searchVal;
    $jsonFlags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
@endphp

<div class="flex flex-col">

    {{-- Sticky header: pagination info + bulk bar + search --}}
    <div class="sticky top-0 z-10 bg-gray-900 border-b border-gray-800">

        {{-- Row 1: row count + pagination --}}
        <div class="flex items-center justify-between px-4 py-2">
            <span class="text-xs text-gray-400">
                {{ number_format($total) }} row{{ $total !== 1 ? 's' : '' }}
                @if($hasSearch) <span class="text-blue-400">(filtered)</span> @endif
                @if($totalPages > 1) &middot; Page {{ $page }} of {{ $totalPages }} @endif
            </span>
            <div class="flex items-center gap-2">
                @if($page > 1)
                    <button class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-2 py-1 rounded transition-colors"
                        onclick="loadTableData('{{ $table }}', {{ $page - 1 }})">← Prev</button>
                @endif
                @if($page < $totalPages)
                    <button class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-2 py-1 rounded transition-colors"
                        onclick="loadTableData('{{ $table }}', {{ $page + 1 }})">Next →</button>
                @endif
            </div>
        </div>

        {{-- Bulk actions bar (visible only when rows are selected) --}}
        @if($hasPk)
        <div id="bulk-actions-bar"
             class="hidden items-center justify-between px-4 py-1.5 border-t border-blue-500/20 bg-blue-500/5">
            <span class="text-xs text-blue-400">
                <span id="selected-count">0</span> rows selected
            </span>
            <div class="flex items-center gap-2">
                <button onclick="clearRowSelection()"
                    class="text-xs text-gray-500 hover:text-gray-300 transition-colors">
                    Clear
                </button>
                <button onclick="openBulkDeleteModal()"
                    class="flex items-center gap-1 text-xs text-white bg-red-600 hover:bg-red-500 px-3 py-1 rounded font-medium transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete selected
                </button>
            </div>
        </div>
        @endif

        {{-- Row 2: search bar --}}
        <div class="flex items-center gap-2 px-4 py-2 border-t border-gray-800/60">
            {{-- Column selector --}}
            <select id="search-col"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 max-w-[140px]"
                onchange="onSearchColChange()">
                <option value="">Column…</option>
                @foreach($colTypes as $col => $type)
                    <option value="{{ $col }}"
                        data-type="{{ $type }}"
                        {{ $searchCol === $col ? 'selected' : '' }}>
                        {{ $col }}{{ $type === 'jsonb' ? ' ⬡' : '' }}
                    </option>
                @endforeach
            </select>

            {{-- Operator selector --}}
            <select id="search-op"
                class="bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="contains"     {{ $searchOp === 'contains'      ? 'selected' : '' }}>contains</option>
                <option value="equals"       {{ $searchOp === 'equals'        ? 'selected' : '' }}>equals</option>
                <option value="starts_with"  {{ $searchOp === 'starts_with'   ? 'selected' : '' }} class="non-jsonb-op">starts with</option>
                <option value="jsonb_contains" {{ $searchOp === 'jsonb_contains' ? 'selected' : '' }} class="jsonb-op" style="display:none">JSON ⊇</option>
            </select>

            {{-- Value input --}}
            <input type="text" id="search-val"
                value="{{ $searchVal ?? '' }}"
                placeholder="Search…"
                class="flex-1 min-w-0 bg-gray-800 border border-gray-700 rounded px-3 py-1 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                onkeydown="if(event.key==='Enter') submitSearch()">

            <button onclick="submitSearch()"
                class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-3 py-1 rounded transition-colors font-medium flex-shrink-0">
                Search
            </button>

            @if($hasSearch)
                <button onclick="clearSearch()"
                    class="text-xs text-gray-400 hover:text-red-400 border border-gray-700 hover:border-red-500/50 px-2 py-1 rounded transition-colors flex-shrink-0"
                    title="Clear search">
                    ✕
                </button>
            @endif
        </div>
    </div>

    {{-- Data grid --}}
    <div>
        @if(empty($rows))
            <div class="flex items-center justify-center h-32 text-gray-500 text-sm">
                {{ $hasSearch ? 'No rows match the search.' : 'No rows found.' }}
            </div>
        @else
            <table class="results-table text-xs text-left">
                <thead>
                    <tr class="border-b border-gray-800">
                        @if($hasPk)
                            <th class="px-3 py-2.5 w-8 text-center border-r border-gray-800"
                                onclick="event.stopPropagation()">
                                <input type="checkbox" id="select-all-rows"
                                    class="w-3 h-3 accent-blue-500 cursor-pointer"
                                    onchange="toggleSelectAll(this)"
                                    title="Select all">
                            </th>
                        @endif
                        @foreach($columns as $col)
                            <th class="px-3 py-2.5 text-gray-400 font-medium whitespace-nowrap border-r border-gray-800 last:border-r-0">
                                <button
                                    class="hover:text-gray-200 transition-colors flex items-center gap-1"
                                    data-table="{{ $table }}"
                                    data-col="{{ $col }}"
                                    onclick="loadSortedTable(this.dataset.table, this.dataset.col)"
                                >
                                    {{ $col }}
                                    @if(($colTypes[$col] ?? '') === 'jsonb')
                                        <span class="text-gray-600 text-[10px]">jsonb</span>
                                    @endif
                                    @if($sortCol === $col)
                                        <span>{{ $sortDir === 'ASC' ? '↑' : '↓' }}</span>
                                    @endif
                                </button>
                            </th>
                        @endforeach
                        @if($hasPk)
                            <th class="px-3 py-2.5 text-gray-600 font-medium whitespace-nowrap w-16 text-center">Actions</th>
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
                            @if($hasPk)
                                <td class="px-3 py-2 w-8 text-center border-r border-gray-800/30"
                                    onclick="event.stopPropagation(); if(event.target.type!=='checkbox') this.querySelector('.row-checkbox').click()">
                                    <input type="checkbox"
                                        class="row-checkbox w-3 h-3 accent-blue-500 cursor-pointer"
                                        value="{{ json_encode($pkValues, $jsonFlags) }}"
                                        onchange="updateRowSelection()">
                                </td>
                            @endif
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
                                        <button type="button"
                                            class="p-1 rounded text-gray-500 hover:text-blue-400 hover:bg-blue-500/10 transition-colors"
                                            title="Edit row"
                                            data-table="{{ $table }}"
                                            data-row="{{ json_encode($row, $jsonFlags) }}"
                                            data-pk-columns="{{ json_encode($pkColumns, $jsonFlags) }}"
                                            onclick="openEditModal(this)">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                        <button type="button"
                                            class="p-1 rounded text-gray-500 hover:text-red-400 hover:bg-red-500/10 transition-colors"
                                            title="Delete row"
                                            data-table="{{ $table }}"
                                            data-pk="{{ json_encode($pkValues, $jsonFlags) }}"
                                            onclick="openDeleteModal(this)">
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
    window._currentTableTotal = {{ $total }};

    // Restore current search state from server (persists across pagination/sort)
    window._currentSearch = {
        col: @json($searchCol),
        val: @json($searchVal),
        op:  @json($searchOp),
    };

    // ── Multi-select ────────────────────────────────────────────────────────
    function updateRowSelection() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const all = document.querySelectorAll('.row-checkbox');
        const count = checked.length;

        const bar = document.getElementById('bulk-actions-bar');
        const countEl = document.getElementById('selected-count');
        if (bar) {
            bar.classList.toggle('hidden', count === 0);
            bar.classList.toggle('flex', count > 0);
        }
        if (countEl) countEl.textContent = count;

        const sa = document.getElementById('select-all-rows');
        if (sa) {
            sa.indeterminate = count > 0 && count < all.length;
            sa.checked = all.length > 0 && count === all.length;
        }
    }

    function toggleSelectAll(cb) {
        document.querySelectorAll('.row-checkbox').forEach(r => r.checked = cb.checked);
        updateRowSelection();
    }

    function clearRowSelection() {
        document.querySelectorAll('.row-checkbox').forEach(r => r.checked = false);
        const sa = document.getElementById('select-all-rows');
        if (sa) { sa.checked = false; sa.indeterminate = false; }
        updateRowSelection();
    }

    window.getSelectedPks = function () {
        return Array.from(document.querySelectorAll('.row-checkbox:checked'))
            .map(cb => JSON.parse(cb.value));
    };

    // ── Search helpers ──────────────────────────────────────────────────────
    function onSearchColChange() {
        const sel   = document.getElementById('search-col');
        const opSel = document.getElementById('search-op');
        if (!sel || !opSel) return;

        const type   = sel.options[sel.selectedIndex]?.dataset?.type ?? '';
        const isJsonb = type === 'jsonb';

        opSel.querySelectorAll('.jsonb-op').forEach(o => o.style.display = isJsonb ? '' : 'none');
        opSel.querySelectorAll('.non-jsonb-op').forEach(o => o.style.display = isJsonb ? 'none' : '');

        if (!isJsonb && opSel.value === 'jsonb_contains') opSel.value = 'contains';

        const input = document.getElementById('search-val');
        if (input) {
            input.placeholder = (isJsonb && opSel.value === 'jsonb_contains')
                ? '{"key": "value"}'
                : 'Search…';
        }
    }

    document.getElementById('search-op')?.addEventListener('change', function () {
        const input  = document.getElementById('search-val');
        const colSel = document.getElementById('search-col');
        const type   = colSel?.options[colSel.selectedIndex]?.dataset?.type ?? '';
        if (input) {
            input.placeholder = (type === 'jsonb' && this.value === 'jsonb_contains')
                ? '{"key": "value"}'
                : 'Search…';
        }
    });

    function submitSearch() {
        const col = document.getElementById('search-col')?.value ?? '';
        const val = document.getElementById('search-val')?.value ?? '';
        const op  = document.getElementById('search-op')?.value ?? 'contains';

        if (!col) { document.getElementById('search-col')?.focus(); return; }

        if (op === 'jsonb_contains') {
            try { JSON.parse(val); } catch {
                alert('Invalid JSON for "JSON ⊇" — enter a valid JSON object, e.g. {"status": "active"}');
                return;
            }
        }

        window._currentSearch = { col, val, op };
        loadTableData(currentTable, 1);
    }

    function clearSearch() {
        window._currentSearch = { col: null, val: null, op: 'contains' };
        loadTableData(currentTable, 1);
    }

    function loadSortedTable(table, col) {
        let dir = 'ASC';
        if (window._currentSortCol === col) {
            dir = window._currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        }
        htmx.ajax('GET', buildTableUrl(table, 1, { sort: col, dir }), {
            target: '#main-content', swap: 'innerHTML',
        });
    }

    // Sync operator visibility with the restored column value
    onSearchColChange();
</script>
