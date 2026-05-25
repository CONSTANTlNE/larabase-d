@php
    $totalPages  = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    $columns     = !empty($rows) ? array_keys($rows[0]) : array_keys($colTypes);
    $hasPk       = !empty($pkColumns);
    $hasFilters  = !empty($filters);
    $jsonFlags   = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;
    $fromRow     = $total === 0 ? 0 : ($page - 1) * $perPage + 1;
    $toRow       = min($page * $perPage, $total);

    /**
     * Parses a PostgreSQL array literal like {a,"b c",NULL} into a PHP array.
     */
    $parsePgArray = function (string $raw): array {
        if (! str_starts_with($raw, '{') || ! str_ends_with($raw, '}')) {
            return [$raw];
        }
        $inner = substr($raw, 1, -1);
        if ($inner === '') {
            return [];
        }

        return str_getcsv($inner);
    };
@endphp

{{-- Column options template (server-rendered once, cloned by JS for each filter row) --}}
<template id="filter-col-options">
    <option value="">Column…</option>
    @foreach($colTypes as $col => $type)
        <option value="{{ $col }}" data-type="{{ $type }}">
            {{ $col }}{{ $type === 'jsonb' ? ' ⬡' : '' }}
        </option>
    @endforeach
</template>

<div class="flex flex-col">

    {{-- Sticky header ─────────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-10 bg-gray-900 border-b border-gray-800">

        {{-- Row 1: range + pagination + per-page picker --}}
        <div class="flex items-center gap-2 px-4 py-1.5">
            {{-- Row count / range --}}
            @if($total === 0)
                <span class="text-xs text-gray-400">0 rows</span>
            @else
                <span class="text-xs text-gray-400">{{ number_format($fromRow) }}–{{ number_format($toRow) }} of {{ number_format($total) }}</span>
            @endif
            @if($hasFilters)
                <span class="text-xs text-blue-400">(filtered)</span>
            @endif

            {{-- Pagination --}}
            @if($totalPages > 1)
                <span class="text-gray-700 select-none">·</span>
                @if($page > 1)
                    <button class="text-xs text-gray-500 hover:text-gray-200 transition-colors"
                        onclick="loadTableData('{{ $table }}', {{ $page - 1 }})">←</button>
                @endif
                <span class="text-xs text-gray-500">{{ $page }}/{{ $totalPages }}</span>
                @if($page < $totalPages)
                    <button class="text-xs text-gray-500 hover:text-gray-200 transition-colors"
                        onclick="loadTableData('{{ $table }}', {{ $page + 1 }})">→</button>
                @endif
            @endif

            {{-- Per-page picker --}}
            <div class="flex items-center gap-1 ml-auto">
                <span class="text-xs text-gray-600">per page</span>
                @foreach([30, 50, 100, 150] as $opt)
                    <button
                        onclick="changePerPage({{ $opt }})"
                        class="text-xs px-1.5 py-0.5 rounded transition-colors {{ $perPage === $opt ? 'bg-blue-600 text-white font-medium' : 'text-gray-500 hover:text-gray-300 hover:bg-gray-800' }}">
                        {{ $opt }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Bulk actions bar (visible only when rows are selected) --}}
        @if($hasPk)
        <div id="bulk-actions-bar"
             class="hidden items-center justify-between px-4 py-1 border-t border-blue-500/20 bg-blue-500/5">
            <span class="text-xs text-blue-400">
                <span id="selected-count">0</span> rows selected
            </span>
            <div class="flex items-center gap-2">
                <button onclick="clearRowSelection()"
                    class="text-xs text-gray-500 hover:text-gray-300 transition-colors">Clear</button>
                <button onclick="openBulkDeleteModal()"
                    class="flex items-center gap-1 text-xs text-white bg-red-600 hover:bg-red-500 px-2.5 py-0.5 rounded font-medium transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Delete selected
                </button>
            </div>
        </div>
        @endif

        {{-- Row 2: multi-filter ────────────────────────────────────────────── --}}
        <div class="px-4 pt-1.5 pb-1.5 border-t border-gray-800/60">
            <div id="filter-rows" class="space-y-1 mb-1.5"></div>
            <div class="flex items-center gap-1.5">
                <button type="button" onclick="addFilterRow()"
                    class="flex items-center gap-1 text-xs text-gray-500 hover:text-gray-300 transition-colors">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add filter
                </button>
                <span class="w-px h-3 bg-gray-700 flex-shrink-0"></span>
                <button type="button" onclick="applyFilters()"
                    class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-2.5 py-0.5 rounded font-medium transition-colors">
                    Apply
                </button>
                <button type="button" id="clear-filters-btn" onclick="clearAllFilters()"
                    class="{{ $hasFilters ? '' : 'hidden' }} text-xs text-gray-500 hover:text-red-400 transition-colors">
                    Clear
                </button>
            </div>
        </div>
    </div>

    {{-- Data grid ──────────────────────────────────────────────────────────── --}}
    <div>
        @if(empty($rows))
            <div class="flex items-center justify-center h-32 text-gray-500 text-sm">
                {{ $hasFilters ? 'No rows match the filters.' : 'No rows found.' }}
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
                                    $colType = $colTypes[$col] ?? '';
                                    $cellStr = is_null($value) ? null
                                        : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
                                    $isArray = $colType === 'ARRAY' && ! is_null($value);
                                    $arrItems = $isArray ? $parsePgArray($cellStr) : [];
                                @endphp
                                <td
                                    class="px-3 py-2 {{ $isArray ? 'max-w-xs' : 'whitespace-nowrap max-w-xs truncate' }} border-r border-gray-800/30 last:border-r-0 text-gray-300 cursor-pointer hover:bg-gray-700/40"
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
                                    @elseif($isArray)
                                        <div class="flex flex-wrap gap-1 py-0.5">
                                            @foreach(array_slice($arrItems, 0, 5) as $item)
                                                <span class="bg-gray-700/80 text-gray-300 text-[10px] px-1.5 py-0.5 rounded font-mono">{{ $item === 'NULL' ? 'NULL' : $item }}</span>
                                            @endforeach
                                            @if(count($arrItems) > 5)
                                                <span class="text-gray-600 text-[10px]">+{{ count($arrItems) - 5 }} more</span>
                                            @endif
                                        </div>
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
    window._currentSortCol  = @json($sortCol);
    window._currentSortDir  = @json($sortDir);
    window._currentTableTotal = {{ $total }};
    window._colTypes        = @json($colTypes);   // col → pg type
    window._colEnums        = @json($colEnums);   // col → [allowed values] for enum columns
    window._currentPerPage  = {{ $perPage }};

    // Restore active filters from server (persists across pagination/sort)
    window._currentFilters = @json($filters);

    // ── Filter row factory ──────────────────────────────────────────────────
    function _makeFilterRow(col, val, op) {
        col = col || '';
        val = val || '';
        op  = op  || 'contains';

        const row = document.createElement('div');
        row.className = 'filter-row flex items-center gap-1.5';

        // Column selector — clone options from server-rendered template
        const colSel = document.createElement('select');
        colSel.className = 'filter-col bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 max-w-[140px] truncate';
        const tpl = document.getElementById('filter-col-options');
        colSel.innerHTML = tpl ? tpl.innerHTML : '<option value="">Column…</option>';
        colSel.value = col;
        colSel.addEventListener('change', function () { _syncFilterOp(row); });

        // Operator selector
        const opSel = document.createElement('select');
        opSel.className = 'filter-op bg-gray-800 border border-gray-700 rounded px-2 py-1 text-xs text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 w-28';
        opSel.innerHTML = `
            <option value="contains">contains</option>
            <option value="equals">equals</option>
            <option value="starts_with" class="non-jsonb-op">starts with</option>
            <option value="jsonb_contains" class="jsonb-op" style="display:none">JSON ⊇</option>
        `;
        opSel.value = op;
        opSel.addEventListener('change', function () { _syncFilterPlaceholder(row); });

        // Value input
        const valInput = document.createElement('input');
        valInput.type = 'text';
        valInput.className = 'filter-val flex-1 min-w-0 bg-gray-800 border border-gray-700 rounded px-3 py-1 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500';
        valInput.placeholder = 'Value…';
        valInput.value = val;
        valInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') applyFilters();
        });

        // Remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'p-1 rounded text-gray-600 hover:text-red-400 hover:bg-red-500/10 transition-colors flex-shrink-0';
        removeBtn.title = 'Remove filter';
        removeBtn.innerHTML = `<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>`;
        removeBtn.addEventListener('click', function () {
            row.remove();
            // If all rows gone, add one empty row
            if (!document.querySelector('.filter-row')) addFilterRow();
        });

        row.append(colSel, opSel, valInput, removeBtn);

        // Sync operator visibility after construction
        _syncFilterOp(row);

        return row;
    }

    function _syncFilterOp(row) {
        const colSel   = row.querySelector('.filter-col');
        const opSel    = row.querySelector('.filter-op');
        const valInput = row.querySelector('.filter-val');
        const type     = (window._colTypes || {})[colSel.value] || '';
        const isJsonb  = type === 'jsonb';

        opSel.querySelectorAll('.jsonb-op').forEach(o => { o.style.display = isJsonb ? '' : 'none'; });
        opSel.querySelectorAll('.non-jsonb-op').forEach(o => { o.style.display = isJsonb ? 'none' : ''; });
        if (!isJsonb && opSel.value === 'jsonb_contains') opSel.value = 'contains';

        _syncFilterPlaceholder(row);
    }

    function _syncFilterPlaceholder(row) {
        const colSel   = row.querySelector('.filter-col');
        const opSel    = row.querySelector('.filter-op');
        const valInput = row.querySelector('.filter-val');
        const type     = (window._colTypes || {})[colSel.value] || '';
        valInput.placeholder = (type === 'jsonb' && opSel.value === 'jsonb_contains')
            ? '{"key": "value"}'
            : 'Value…';
    }

    function addFilterRow(col, val, op) {
        const container = document.getElementById('filter-rows');
        if (!container) return;
        const row = _makeFilterRow(col, val, op);
        container.appendChild(row);
        row.querySelector('.filter-col').focus();
    }

    function _initFilterRows() {
        const container = document.getElementById('filter-rows');
        if (!container) return;
        container.innerHTML = '';
        const filters = window._currentFilters || [];
        if (filters.length === 0) {
            container.appendChild(_makeFilterRow());
        } else {
            filters.forEach(f => container.appendChild(_makeFilterRow(f.col, f.val, f.op)));
        }
    }

    function applyFilters() {
        const rows    = document.querySelectorAll('.filter-row');
        const filters = [];
        let jsonErr   = false;

        rows.forEach(row => {
            const col = row.querySelector('.filter-col').value;
            const val = row.querySelector('.filter-val').value.trim();
            const op  = row.querySelector('.filter-op').value;
            if (!col || val === '') return;
            if (op === 'jsonb_contains') {
                try { JSON.parse(val); } catch { jsonErr = true; return; }
            }
            filters.push({ col, val, op });
        });

        if (jsonErr) {
            alert('One of the JSON ⊇ values is not valid JSON.\nExample: {"status": "active"}');
            return;
        }

        window._currentFilters = filters;
        loadTableData(currentTable, 1);
    }

    function clearAllFilters() {
        window._currentFilters = [];
        _initFilterRows();
        // Hide clear button
        const btn = document.getElementById('clear-filters-btn');
        if (btn) btn.classList.add('hidden');
        loadTableData(currentTable, 1);
    }

    // ── Multi-select ────────────────────────────────────────────────────────
    function updateRowSelection() {
        const checked = document.querySelectorAll('.row-checkbox:checked');
        const all     = document.querySelectorAll('.row-checkbox');
        const count   = checked.length;

        const bar     = document.getElementById('bulk-actions-bar');
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

    // ── Sort ────────────────────────────────────────────────────────────────
    function loadSortedTable(table, col) {
        let dir = 'ASC';
        if (window._currentSortCol === col) {
            dir = window._currentSortDir === 'ASC' ? 'DESC' : 'ASC';
        }
        htmx.ajax('GET', buildTableUrl(table, 1, { sort: col, dir }), {
            target: '#main-content', swap: 'innerHTML',
        });
    }

    // ── Per-page ────────────────────────────────────────────────────────────
    function changePerPage(n) {
        window._currentPerPage = n;
        loadTableData(currentTable, 1);
    }

    // Bootstrap filter rows on every render
    _initFilterRows();
</script>
