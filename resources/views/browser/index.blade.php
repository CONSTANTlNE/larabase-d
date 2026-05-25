@extends('layouts.app')
@section('title', $connection->name . ' — LaraBase-D')

@push('head')
{{-- CodeMirror 6 via esm.sh (deduplicates @codemirror/state so instanceof checks work) --}}
<script type="module">
    import { EditorView, keymap, lineNumbers, highlightActiveLine } from 'https://esm.sh/@codemirror/view@6';
    import { EditorState } from 'https://esm.sh/@codemirror/state@6';
    import { sql } from 'https://esm.sh/@codemirror/lang-sql@6';
    import { oneDark } from 'https://esm.sh/@codemirror/theme-one-dark@6';
    import { defaultKeymap, history, historyKeymap } from 'https://esm.sh/@codemirror/commands@6';
    import { closeBrackets, closeBracketsKeymap } from 'https://esm.sh/@codemirror/autocomplete@6';

    function runQuery() {
        const sql = window.cmEditor ? window.cmEditor.state.doc.toString() : '';
        if (!sql.trim()) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

        htmx.ajax('POST', '{{ route('browser.query') }}', {
            target: '#query-results',
            swap: 'innerHTML',
            values: { sql: sql, _token: csrfToken }
        });
    }

    function initEditor() {
        const editorEl = document.getElementById('cm-editor');
        if (!editorEl) return;

        const runQueryKey = {
            key: 'Ctrl-Enter',
            mac: 'Cmd-Enter',
            run: () => { runQuery(); return true; }
        };

        const startState = EditorState.create({
            doc: '',
            extensions: [
                lineNumbers(),
                highlightActiveLine(),
                history(),
                closeBrackets(),
                sql(),
                oneDark,
                keymap.of([
                    ...defaultKeymap,
                    ...historyKeymap,
                    ...closeBracketsKeymap,
                    runQueryKey,
                ]),
                EditorView.theme({
                    '&': { height: '100%', fontSize: '13px' },
                    '.cm-scroller': { overflow: 'auto', fontFamily: 'ui-monospace, monospace' },
                    '.cm-content': { padding: '8px 0' },
                }),
            ],
        });

        window.cmEditor = new EditorView({
            state: startState,
            parent: editorEl,
        });
    }

    window.cmSetContent = function(content) {
        if (!window.cmEditor) return;
        window.cmEditor.dispatch({
            changes: { from: 0, to: window.cmEditor.state.doc.length, insert: content }
        });
    };

    window.runQuery = runQuery;

    // Init after DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEditor);
    } else {
        initEditor();
    }
</script>
<style>
    .cm-editor { background: #111827 !important; }
    .cm-editor .cm-gutters { background: #111827 !important; border-right: 1px solid #1f2937 !important; }
    /* Table styles */
    .results-table { border-collapse: collapse; width: 100%; }
    .results-table th { background: #111827; position: sticky; top: 0; z-index: 1; }
</style>
@endpush

@section('content')
<div class="flex h-screen bg-gray-950 overflow-hidden">

    {{-- LEFT SIDEBAR --}}
    <aside class="w-64 flex-shrink-0 bg-gray-900 border-r border-gray-800 flex flex-col">
        {{-- Connection header --}}
        <div class="px-4 py-3 border-b border-gray-800">
            <div class="flex items-center gap-2 mb-1">
                <span class="w-2 h-2 bg-green-500 rounded-full flex-shrink-0"></span>
                <span class="text-sm font-medium text-white truncate">{{ $connection->name }}</span>
            </div>
            <p class="text-xs text-gray-500 truncate pl-4">{{ $connection->database }}</p>
            <a href="{{ route('browser.disconnect') }}" class="text-xs text-gray-500 hover:text-red-400 transition-colors pl-4 mt-1 inline-block">
                Disconnect
            </a>
        </div>

        {{-- Search --}}
        <div class="px-3 py-2 border-b border-gray-800">
            <input
                type="text"
                id="table-search"
                placeholder="Search tables..."
                class="w-full bg-gray-800 border border-gray-700 rounded-md px-3 py-1.5 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                oninput="filterTables(this.value)"
            >
        </div>

        {{-- Table list --}}
        <div class="flex-1 overflow-y-auto" id="sidebar-tables"
            hx-get="{{ route('browser.tables') }}"
            hx-trigger="load">
            <div class="px-4 py-6 flex items-center justify-center">
                <svg class="lb-spin w-5 h-5 text-blue-500" viewBox="0 0 24 24" fill="none">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                            stroke-dasharray="32" stroke-dashoffset="10" stroke-linecap="round"/>
                </svg>
            </div>
        </div>

        {{-- Bottom toggles --}}
        <div class="border-t border-gray-800">
            <button
                class="w-full flex items-center gap-2 px-4 py-3 text-xs text-gray-400 hover:text-gray-200 hover:bg-gray-800 transition-colors"
                hx-get="{{ route('browser.saved-queries') }}"
                hx-target="#main-content"
                hx-swap="innerHTML"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                Saved Queries
            </button>
            <button
                class="w-full flex items-center gap-2 px-4 py-3 text-xs text-gray-400 hover:text-gray-200 hover:bg-gray-800 transition-colors border-t border-gray-800"
                hx-get="{{ route('browser.history') }}"
                hx-target="#main-content"
                hx-swap="innerHTML"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                History
            </button>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        {{-- Tab bar --}}
        <div class="bg-gray-900 border-b border-gray-800 flex items-center px-4 gap-1" id="tab-bar">
            <span class="text-xs text-gray-500 mr-2 py-3" id="active-table-name"></span>
            <button id="tab-data" onclick="activateTab('data')"
                class="tab-btn text-sm px-4 py-3 border-b-2 border-blue-500 text-blue-400 font-medium transition-colors">
                Data
            </button>
            <button id="tab-structure" onclick="activateTab('structure')"
                class="tab-btn text-sm px-4 py-3 border-b-2 border-transparent text-gray-400 hover:text-gray-200 transition-colors">
                Structure
            </button>
            <button id="tab-query" onclick="activateTab('query')"
                class="tab-btn text-sm px-4 py-3 border-b-2 border-transparent text-gray-400 hover:text-gray-200 transition-colors">
                Query
            </button>
        </div>

        {{-- Content area --}}
        <div class="flex-1 overflow-auto" id="main-content">
            @include('partials.browser-welcome')
        </div>

        {{-- Query editor panel (always rendered, shown/hidden) --}}
        <div id="query-panel" class="hidden flex-col border-t border-gray-800" style="height: 50%;">
            <div class="flex items-center justify-between px-4 py-2 bg-gray-900 border-b border-gray-800">
                <span class="text-xs text-gray-400 font-medium">SQL Editor</span>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-600">Ctrl+Enter to run</span>
                    <button
                        onclick="runQuery()"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-xs px-3 py-1 rounded transition-colors font-medium"
                    >
                        Run
                    </button>
                    <button
                        id="save-query-btn"
                        onclick="document.getElementById('save-query-dialog').classList.toggle('hidden')"
                        class="text-gray-400 hover:text-gray-200 border border-gray-700 text-xs px-3 py-1 rounded transition-colors"
                    >
                        Save
                    </button>
                </div>
            </div>

            {{-- Save dialog --}}
            <div id="save-query-dialog" class="hidden absolute right-4 top-auto z-50 bg-gray-800 border border-gray-700 rounded-lg p-4 shadow-xl" style="margin-top: 40px;">
                <form
                    hx-post="{{ route('browser.saved-queries.store') }}"
                    hx-target="#main-content"
                    hx-swap="innerHTML"
                    hx-on::after-request="document.getElementById('save-query-dialog').classList.add('hidden')"
                    class="flex items-center gap-2"
                >
                    @csrf
                    <input type="hidden" name="sql" id="save-query-sql">
                    <input type="text" name="name" placeholder="Query name" required
                        class="bg-gray-700 border border-gray-600 rounded px-3 py-1.5 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500 w-48">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-sm px-3 py-1.5 rounded transition-colors">
                        Save
                    </button>
                </form>
            </div>

            <div class="flex-1 overflow-hidden bg-gray-900" id="cm-editor"></div>
            <div id="query-results" class="overflow-auto border-t border-gray-800" style="max-height: 300px; min-height: 60px;"></div>
        </div>
    </main>
</div>

{{-- ── CELL EXPAND MODAL ──────────────────────────────────────────────── --}}
<div id="cell-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-6"
     onclick="if(event.target===this)closeCellModal()">
    <div class="absolute inset-0 bg-black/60"></div>
    <div class="relative bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl max-h-[80vh] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800 flex-shrink-0">
            <span id="cell-modal-title" class="text-sm font-medium text-gray-300 font-mono"></span>
            <button onclick="closeCellModal()" class="text-gray-500 hover:text-gray-200 text-xl leading-none transition-colors">&times;</button>
        </div>
        <div id="cell-modal-body" class="flex-1 overflow-auto p-4 text-sm text-gray-100 font-mono whitespace-pre-wrap break-words leading-relaxed"></div>
        <div class="px-4 py-3 border-t border-gray-800 flex justify-end flex-shrink-0">
            <button onclick="closeCellModal()"
                class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-3 py-1.5 rounded transition-colors">
                Close
            </button>
        </div>
    </div>
</div>

{{-- ── DELETE CONFIRM MODAL ────────────────────────────────────────────── --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-6">
    <div class="absolute inset-0 bg-black/60" onclick="closeDeleteModal()"></div>
    <div class="relative bg-gray-900 border border-gray-700 rounded-xl w-full max-w-md shadow-2xl">
        <div class="p-5">
            <div class="flex items-start gap-3 mb-5">
                <div class="w-8 h-8 rounded-full bg-red-500/15 flex items-center justify-center flex-shrink-0">
                    <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-white mb-1">Delete Row</h3>
                    <p id="delete-modal-info" class="text-xs text-gray-400 leading-relaxed"></p>
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button onclick="closeDeleteModal()"
                    class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-3 py-1.5 rounded transition-colors">
                    Cancel
                </button>
                <button id="confirm-delete-btn"
                    class="text-xs text-white bg-red-600 hover:bg-red-500 px-3 py-1.5 rounded font-medium transition-colors">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── EDIT ROW MODAL ──────────────────────────────────────────────────── --}}
<div id="edit-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-6">
    <div class="absolute inset-0 bg-black/60" onclick="closeEditModal()"></div>
    <div class="relative bg-gray-900 border border-gray-700 rounded-xl w-full max-w-2xl max-h-[85vh] flex flex-col shadow-2xl">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-800 flex-shrink-0">
            <span class="text-sm font-semibold text-white">Edit Row</span>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-200 text-xl leading-none transition-colors">&times;</button>
        </div>
        <div id="edit-modal-fields" class="flex-1 overflow-y-auto p-4 space-y-4 min-h-0"></div>
        <div id="edit-modal-error"
             class="hidden px-4 py-2 text-xs text-red-400 bg-red-500/10 border-t border-red-500/20 flex-shrink-0"></div>
        <div class="px-4 py-3 border-t border-gray-800 flex justify-end gap-2 flex-shrink-0">
            <button onclick="closeEditModal()"
                class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 px-3 py-1.5 rounded transition-colors">
                Cancel
            </button>
            <button id="confirm-edit-btn"
                class="text-xs text-white bg-blue-600 hover:bg-blue-500 px-3 py-1.5 rounded font-medium transition-colors">
                Save Changes
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    let currentTable = null;
    let currentPage = 1;

    function activateTab(tab) {
        // Update tab styles
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-400', 'font-medium');
            btn.classList.add('border-transparent', 'text-gray-400');
        });
        const activeBtn = document.getElementById('tab-' + tab);
        activeBtn.classList.add('border-blue-500', 'text-blue-400', 'font-medium');
        activeBtn.classList.remove('border-transparent', 'text-gray-400');

        const queryPanel = document.getElementById('query-panel');
        const mainContent = document.getElementById('main-content');

        if (tab === 'query') {
            queryPanel.classList.remove('hidden');
            queryPanel.classList.add('flex');
            mainContent.classList.add('hidden');
        } else {
            queryPanel.classList.add('hidden');
            queryPanel.classList.remove('flex');
            mainContent.classList.remove('hidden');

            if (currentTable) {
                if (tab === 'data') {
                    loadTableData(currentTable, currentPage);
                } else if (tab === 'structure') {
                    loadTableStructure(currentTable);
                }
            }
        }
    }

    function selectTable(tableName) {
        currentTable = tableName;
        currentPage = 1;
        // Display only the table part (strip schema prefix)
        const displayName = tableName.includes('.') ? tableName.split('.').pop() : tableName;
        document.getElementById('active-table-name').textContent = displayName;

        // Highlight in sidebar
        document.querySelectorAll('.table-item').forEach(el => {
            el.classList.toggle('bg-gray-800', el.dataset.table === tableName);
            el.classList.toggle('text-blue-400', el.dataset.table === tableName);
        });

        // Check active tab
        const activeTab = document.querySelector('.tab-btn.border-blue-500');
        const tabId = activeTab ? activeTab.id.replace('tab-', '') : 'data';

        if (tabId === 'query') {
            activateTab('data');
        } else if (tabId === 'structure') {
            loadTableStructure(tableName);
        } else {
            loadTableData(tableName, 1);
        }
    }

    function loadTableData(table, page) {
        htmx.ajax('GET', `/browser/tables/${encodeURIComponent(table)}?page=${page}`, {
            target: '#main-content',
            swap: 'innerHTML',
        });
    }

    function loadTableStructure(table) {
        htmx.ajax('GET', `/browser/tables/${encodeURIComponent(table)}/structure`, {
            target: '#main-content',
            swap: 'innerHTML',
        });
    }

    function filterTables(query) {
        document.querySelectorAll('.table-item').forEach(el => {
            const match = el.dataset.table.toLowerCase().includes(query.toLowerCase());
            el.style.display = match ? '' : 'none';
        });
    }

    // Before saving query, populate hidden field
    document.getElementById('save-query-btn').addEventListener('click', function() {
        const sql = window.cmEditor ? window.cmEditor.state.doc.toString() : '';
        document.getElementById('save-query-sql').value = sql;
    });

    // History item click → restore to editor
    document.body.addEventListener('click', function(e) {
        const item = e.target.closest('[data-restore-sql]');
        if (item) {
            const sql = item.dataset.restoreSql;
            if (window.cmSetContent) window.cmSetContent(sql);
            activateTab('query');
        }
    });

    // ── Helpers ────────────────────────────────────────────────────────────
    function modalShow(id) {
        const el = document.getElementById(id);
        el.classList.remove('hidden');
        el.classList.add('flex');
    }
    function modalHide(id) {
        const el = document.getElementById(id);
        el.classList.add('hidden');
        el.classList.remove('flex');
    }

    // Close any open modal on Escape
    document.addEventListener('keydown', function(e) {
        if (e.key !== 'Escape') return;
        ['cell-modal', 'delete-modal', 'edit-modal'].forEach(modalHide);
    });

    // ── Cell Expand Modal ───────────────────────────────────────────────────
    window.openCellModal = function(td) {
        const col = td.dataset.col;
        const isNull = td.dataset.isNull === '1';
        document.getElementById('cell-modal-title').textContent = col;
        const body = document.getElementById('cell-modal-body');
        if (isNull) {
            body.innerHTML = '<span class="text-gray-600 italic">NULL</span>';
        } else {
            body.textContent = td.dataset.val;
        }
        modalShow('cell-modal');
    };
    window.closeCellModal = function() { modalHide('cell-modal'); };

    // ── Delete Modal ────────────────────────────────────────────────────────
    let _deleteState = null;

    window.openDeleteModal = function(btn) {
        const table = btn.dataset.table;
        const pkValues = JSON.parse(btn.dataset.pk);
        _deleteState = { table, pkValues };
        const info = Object.entries(pkValues)
            .map(([k, v]) => `${k} = ${v}`)
            .join(', ');
        document.getElementById('delete-modal-info').textContent =
            'This will permanently delete the row where: ' + info;
        const confirmBtn = document.getElementById('confirm-delete-btn');
        confirmBtn.disabled = false;
        confirmBtn.textContent = 'Delete';
        modalShow('delete-modal');
    };
    window.closeDeleteModal = function() {
        modalHide('delete-modal');
        _deleteState = null;
    };

    document.getElementById('confirm-delete-btn').addEventListener('click', async function() {
        if (!_deleteState) return;
        this.disabled = true;
        this.textContent = 'Deleting…';
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch(
                `/browser/tables/${encodeURIComponent(_deleteState.table)}/rows`,
                {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ pk: _deleteState.pkValues }),
                }
            );
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.error || 'Delete failed');
            closeDeleteModal();
            loadTableData(currentTable, currentPage);
        } catch (e) {
            this.disabled = false;
            this.textContent = 'Delete';
            alert('Error: ' + e.message);
        }
    });

    // ── Edit Modal ──────────────────────────────────────────────────────────
    let _editState = null;

    window.openEditModal = function(btn) {
        const table = btn.dataset.table;
        const row = JSON.parse(btn.dataset.row);
        const pkColumns = JSON.parse(btn.dataset.pkColumns);
        _editState = { table, row, pkColumns };

        const container = document.getElementById('edit-modal-fields');
        container.innerHTML = '';
        document.getElementById('edit-modal-error').classList.add('hidden');
        const saveBtn = document.getElementById('confirm-edit-btn');
        saveBtn.disabled = false;
        saveBtn.textContent = 'Save Changes';

        Object.entries(row).forEach(([col, value]) => {
            const isPk = pkColumns.includes(col);
            const isNull = value === null;
            const strValue = isNull ? '' : String(value);
            const isLong = !isNull && strValue.length > 80;

            const field = document.createElement('div');
            field.className = 'space-y-1.5';

            // Label row
            const labelRow = document.createElement('div');
            labelRow.className = 'flex items-center gap-2';
            const label = document.createElement('label');
            label.className = 'text-xs font-medium text-gray-400';
            label.textContent = col;
            labelRow.appendChild(label);
            if (isPk) {
                const badge = document.createElement('span');
                badge.className = 'text-xs text-amber-500 bg-amber-500/10 px-1.5 py-0.5 rounded';
                badge.textContent = 'PK';
                labelRow.appendChild(badge);
            }
            if (isNull && !isPk) {
                const nullBadge = document.createElement('span');
                nullBadge.className = 'text-xs text-gray-600 bg-gray-800 px-1.5 py-0.5 rounded null-badge-' + col;
                nullBadge.textContent = 'NULL';
                labelRow.appendChild(nullBadge);
            }
            field.appendChild(labelRow);

            if (isPk) {
                // Readonly for PK columns
                const input = document.createElement('input');
                input.type = 'text';
                input.value = strValue;
                input.readOnly = true;
                input.className = 'w-full bg-gray-800/50 border border-gray-700 rounded px-3 py-1.5 text-xs text-gray-500 font-mono cursor-not-allowed';
                field.appendChild(input);
            } else {
                // Editable input or textarea
                const input = isLong
                    ? document.createElement('textarea')
                    : document.createElement('input');

                if (!isLong) input.type = 'text';
                else input.rows = 5;

                input.className = 'w-full bg-gray-800 border border-gray-700 rounded px-3 py-1.5 text-xs text-gray-100 font-mono focus:outline-none focus:ring-1 focus:ring-blue-500 resize-y';
                input.dataset.column = col;
                input.dataset.isNull = isNull ? 'true' : 'false';
                input.value = strValue;
                if (isNull) input.classList.add('opacity-50');

                input.addEventListener('input', function() {
                    this.dataset.isNull = 'false';
                    this.classList.remove('opacity-50');
                    const badge = container.querySelector('.null-badge-' + col);
                    if (badge) badge.remove();
                });

                field.appendChild(input);

                // NULL toggle
                const nullRow = document.createElement('div');
                nullRow.className = 'flex items-center gap-1.5';
                const cb = document.createElement('input');
                cb.type = 'checkbox';
                cb.checked = isNull;
                cb.className = 'w-3 h-3 accent-blue-500';
                cb.addEventListener('change', function() {
                    if (this.checked) {
                        input.value = '';
                        input.dataset.isNull = 'true';
                        input.classList.add('opacity-50');
                        // show null badge
                        if (!labelRow.querySelector('.null-badge-' + col)) {
                            const b = document.createElement('span');
                            b.className = 'text-xs text-gray-600 bg-gray-800 px-1.5 py-0.5 rounded null-badge-' + col;
                            b.textContent = 'NULL';
                            labelRow.appendChild(b);
                        }
                    } else {
                        input.dataset.isNull = 'false';
                        input.classList.remove('opacity-50');
                        const b = labelRow.querySelector('.null-badge-' + col);
                        if (b) b.remove();
                        input.focus();
                    }
                });
                const cbLabel = document.createElement('label');
                cbLabel.className = 'text-xs text-gray-600 select-none cursor-pointer';
                cbLabel.textContent = 'Set to NULL';
                cbLabel.addEventListener('click', () => { cb.checked = !cb.checked; cb.dispatchEvent(new Event('change')); });
                nullRow.appendChild(cb);
                nullRow.appendChild(cbLabel);
                field.appendChild(nullRow);
            }

            container.appendChild(field);
        });

        modalShow('edit-modal');
    };
    window.closeEditModal = function() {
        modalHide('edit-modal');
        _editState = null;
    };

    document.getElementById('confirm-edit-btn').addEventListener('click', async function() {
        if (!_editState) return;
        const { table, row, pkColumns } = _editState;

        const pkValues = {};
        pkColumns.forEach(col => { pkValues[col] = row[col]; });

        const newValues = {};
        const nullColumns = [];

        document.querySelectorAll('#edit-modal-fields [data-column]').forEach(input => {
            const col = input.dataset.column;
            if (input.dataset.isNull === 'true') {
                nullColumns.push(col);
            } else {
                newValues[col] = input.value;
            }
        });

        this.disabled = true;
        this.textContent = 'Saving…';

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const resp = await fetch(
                `/browser/tables/${encodeURIComponent(table)}/rows`,
                {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({ pk: pkValues, values: newValues, null_columns: nullColumns }),
                }
            );
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.error || 'Update failed');
            closeEditModal();
            loadTableData(currentTable, currentPage);
        } catch (e) {
            const errDiv = document.getElementById('edit-modal-error');
            errDiv.textContent = e.message;
            errDiv.classList.remove('hidden');
            this.disabled = false;
            this.textContent = 'Save Changes';
        }
    });
</script>
@endpush
