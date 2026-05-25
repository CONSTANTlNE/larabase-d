@php
    $availableCount = count($rows) - $installedCount;
@endphp

<div class="p-4">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-white">Extensions</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                <span class="text-green-400 font-medium">{{ $installedCount }}</span> installed
                &nbsp;·&nbsp;
                <span class="text-gray-500">{{ $availableCount }} available</span>
            </p>
        </div>
    </div>

    {{-- Toolbar --}}
    <div class="flex items-center gap-2 mb-3">
        {{-- Filter tabs --}}
        <div class="flex items-center bg-gray-800/60 border border-gray-700/60 rounded-lg p-0.5 gap-0.5 text-xs">
            <button
                onclick="filterExts('all')"
                id="ext-tab-all"
                class="ext-tab px-3 py-1.5 rounded-md font-medium transition-all bg-blue-600 text-white shadow-sm">
                All <span class="opacity-75 ml-0.5">{{ count($rows) }}</span>
            </button>
            <button
                onclick="filterExts('installed')"
                id="ext-tab-installed"
                class="ext-tab px-3 py-1.5 rounded-md font-medium transition-all text-gray-300 hover:text-white hover:bg-gray-700/60">
                Installed <span class="text-green-400 ml-0.5">{{ $installedCount }}</span>
            </button>
            <button
                onclick="filterExts('available')"
                id="ext-tab-available"
                class="ext-tab px-3 py-1.5 rounded-md font-medium transition-all text-gray-300 hover:text-white hover:bg-gray-700/60">
                Available <span class="text-sky-400 ml-0.5">{{ $availableCount }}</span>
            </button>
        </div>

        {{-- Search --}}
        <input
            type="text"
            id="ext-search"
            placeholder="Search extensions…"
            oninput="searchExts(this.value)"
            class="flex-1 bg-gray-800 border border-gray-700 rounded px-3 py-1 text-xs text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
        >
    </div>

    {{-- Extension list --}}
    <div id="ext-list" class="space-y-1.5">
        @foreach($rows as $ext)
            @php
                $installed = (int) $ext['is_installed'] === 1;
                $name      = $ext['name'];
                $version   = $installed ? $ext['installed_version'] : $ext['default_version'];
                $comment   = $ext['comment'] ?? '';
                $schema    = $ext['schema_name'] ?? null;
                $installSql = 'CREATE EXTENSION IF NOT EXISTS ' . $name . ';';
                $dropSql    = 'DROP EXTENSION IF EXISTS ' . $name . ';';
            @endphp
            <div
                class="ext-item group flex items-start gap-3 px-3 py-2.5 rounded-lg border transition-colors
                       {{ $installed
                            ? 'bg-green-500/5 border-green-500/15 hover:border-green-500/30'
                            : 'bg-gray-800/30 border-gray-800 hover:border-gray-700' }}"
                data-installed="{{ $installed ? '1' : '0' }}"
                data-name="{{ strtolower($name) }}"
                data-comment="{{ strtolower($comment) }}"
            >
                {{-- Status dot --}}
                <div class="flex-shrink-0 mt-0.5">
                    @if($installed)
                        <span class="w-2 h-2 rounded-full bg-green-500 block" title="Installed"></span>
                    @else
                        <span class="w-2 h-2 rounded-full bg-gray-600 block" title="Not installed"></span>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center flex-wrap gap-1.5 mb-0.5">
                        <span class="font-mono text-xs font-semibold {{ $installed ? 'text-white' : 'text-gray-200' }}">
                            {{ $name }}
                        </span>

                        @if($version)
                            <span class="text-[10px] px-1.5 py-0.5 rounded font-mono
                                         {{ $installed ? 'bg-green-500/20 text-green-300' : 'bg-sky-500/15 text-sky-400' }}">
                                v{{ $version }}
                            </span>
                        @endif

                        @if($installed && $schema)
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-blue-500/10 text-blue-400 font-mono"
                                  title="Installed in schema">
                                {{ $schema }}
                            </span>
                        @endif

                        @if($installed && $ext['default_version'] && $ext['installed_version'] && $ext['default_version'] !== $ext['installed_version'])
                            <span class="text-[10px] px-1.5 py-0.5 rounded bg-amber-500/10 text-amber-400"
                                  title="A newer version is available">
                                → v{{ $ext['default_version'] }} available
                            </span>
                        @endif
                    </div>

                    @if($comment)
                        <p class="text-[11px] {{ $installed ? 'text-gray-400' : 'text-gray-500' }} leading-relaxed truncate">
                            {{ $comment }}
                        </p>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="flex-shrink-0 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    @if($installed)
                        <button
                            class="text-[10px] text-gray-500 hover:text-red-400 border border-gray-700 hover:border-red-500/30 px-1.5 py-0.5 rounded transition-colors font-mono"
                            data-restore-sql="{{ $dropSql }}"
                            title="Load DROP EXTENSION in editor"
                        >DROP</button>
                    @else
                        <button
                            class="text-[10px] text-gray-500 hover:text-green-400 border border-gray-700 hover:border-green-500/30 px-1.5 py-0.5 rounded transition-colors font-mono"
                            data-restore-sql="{{ $installSql }}"
                            title="Load CREATE EXTENSION in editor"
                        >INSTALL</button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Empty state --}}
    <div id="ext-empty" class="hidden flex items-center justify-center py-12 text-gray-500 text-sm">
        No extensions match your filter.
    </div>
</div>

<script>
    let _extFilter = 'all';
    let _extSearch = '';

    function filterExts(tab) {
        _extFilter = tab;

        document.querySelectorAll('.ext-tab').forEach(btn => {
            const isActive = btn.id === 'ext-tab-' + tab;
            btn.classList.toggle('bg-blue-600',      isActive);
            btn.classList.toggle('text-white',       isActive);
            btn.classList.toggle('shadow-sm',        isActive);
            btn.classList.toggle('text-gray-300',    !isActive);
            btn.classList.toggle('hover:text-white', !isActive);
            btn.classList.toggle('hover:bg-gray-700/60', !isActive);
        });

        _applyExtFilters();
    }

    function searchExts(query) {
        _extSearch = query.toLowerCase().trim();
        _applyExtFilters();
    }

    function _applyExtFilters() {
        const items = document.querySelectorAll('.ext-item');
        let visible = 0;

        items.forEach(el => {
            const isInstalled = el.dataset.installed === '1';
            const name        = el.dataset.name || '';
            const comment     = el.dataset.comment || '';

            const tabMatch = _extFilter === 'all'
                || (_extFilter === 'installed' && isInstalled)
                || (_extFilter === 'available' && !isInstalled);

            const searchMatch = _extSearch === ''
                || name.includes(_extSearch)
                || comment.includes(_extSearch);

            const show = tabMatch && searchMatch;
            el.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        const empty = document.getElementById('ext-empty');
        if (empty) empty.classList.toggle('hidden', visible > 0);
    }
</script>
