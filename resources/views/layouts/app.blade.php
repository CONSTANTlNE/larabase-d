<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LaraBase-D')</title>

    {{-- Apply theme before first paint to prevent flash --}}
    <script>
        (function () {
            // Default is light; only switch to dark if explicitly saved
            if (localStorage.getItem('lb-theme') === 'dark') {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        })();
    </script>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { 'gray-950': '#030712' }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/htmx.org@2.0.3"></script>

    <style>
        /* ── HTMX indicator override ───────────────────────────── */
        .htmx-indicator { display: none !important; }
        .htmx-request > .htmx-indicator,
        .htmx-request.htmx-indicator { display: flex !important; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .lb-spin { animation: spin 0.7s linear infinite; }

        /* ── Dark theme (class="dark" on <html>) ───────────────── */
        /* backgrounds */
        html.dark body                    { background-color: #030712; color: #f3f4f6; }
        html.dark .bg-gray-950            { background-color: #030712; }
        html.dark .bg-gray-900            { background-color: #111827; }
        html.dark .bg-gray-800            { background-color: #1f2937; }
        html.dark .bg-gray-800\/40        { background-color: rgba(31,41,55,.40); }
        html.dark .bg-gray-800\/50        { background-color: rgba(31,41,55,.50); }
        html.dark .bg-gray-700\/40        { background-color: rgba(55,65,81,.40); }
        /* text */
        html.dark .text-white             { color: #ffffff; }
        html.dark .text-gray-100          { color: #f3f4f6; }
        html.dark .text-gray-200          { color: #e5e7eb; }
        html.dark .text-gray-300          { color: #d1d5db; }
        html.dark .text-gray-400          { color: #9ca3af; }
        html.dark .text-gray-500          { color: #6b7280; }
        html.dark .text-gray-600          { color: #4b5563; }
        /* borders */
        html.dark .border-gray-800        { border-color: #1f2937; }
        html.dark .border-gray-700        { border-color: #374151; }
        html.dark .border-gray-800\/30    { border-color: rgba(31,41,55,.30); }
        html.dark .border-gray-800\/50    { border-color: rgba(31,41,55,.50); }
        html.dark .border-gray-800\/60    { border-color: rgba(31,41,55,.60); }
        /* hovers */
        html.dark .hover\:bg-gray-800:hover   { background-color: #1f2937; }
        html.dark .hover\:bg-gray-700\/40:hover { background-color: rgba(55,65,81,.40); }
        html.dark .hover\:text-gray-200:hover { color: #e5e7eb; }
        html.dark .hover\:text-white:hover    { color: #ffffff; }
        /* blue accent */
        html.dark .text-blue-400          { color: #60a5fa; }
        /* code mirror */
        html.dark .cm-editor              { background: #111827 !important; }
        html.dark .cm-editor .cm-gutters  { background: #111827 !important; border-right: 1px solid #1f2937 !important; }
        /* table header sticky */
        html.dark .results-table th       { background: #111827; }

        /* ── Light theme (default — html:not(.dark) beats Tailwind CDN specificity) ── */
        body                                          { background-color: #F5F1EB; color: #1C1A18; }
        html:not(.dark) .bg-gray-950                  { background-color: #F5F1EB; }
        html:not(.dark) .bg-gray-900                  { background-color: #FFFFFF; }
        html:not(.dark) .bg-gray-800                  { background-color: #EDE8E0; }
        html:not(.dark) .bg-gray-800\/40              { background-color: rgba(237,232,224,.40); }
        html:not(.dark) .bg-gray-800\/50              { background-color: rgba(237,232,224,.50); }
        html:not(.dark) .bg-gray-700\/40              { background-color: rgba(221,215,207,.40); }
        /* text */
        html:not(.dark) .text-white                   { color: #111110; }
        html:not(.dark) .text-gray-100                { color: #111110; }
        html:not(.dark) .text-gray-200                { color: #2E2B28; }
        html:not(.dark) .text-gray-300                { color: #46423D; }
        html:not(.dark) .text-gray-400                { color: #726E69; }
        html:not(.dark) .text-gray-500                { color: #9E9A96; }
        html:not(.dark) .text-gray-600                { color: #BDB9B4; }
        /* borders */
        html:not(.dark) .border-gray-800              { border-color: #E5DFD7; }
        html:not(.dark) .border-gray-700              { border-color: #D4CEC6; }
        html:not(.dark) .border-gray-600              { border-color: #BFBAB3; }
        html:not(.dark) .border-gray-800\/30          { border-color: rgba(229,223,215,.30); }
        html:not(.dark) .border-gray-800\/50          { border-color: rgba(229,223,215,.50); }
        html:not(.dark) .border-gray-800\/60          { border-color: rgba(229,223,215,.60); }
        /* hovers */
        html:not(.dark) .hover\:bg-gray-800:hover     { background-color: #EDE8E0; }
        html:not(.dark) .hover\:bg-gray-700\/40:hover { background-color: rgba(221,215,207,.40); }
        html:not(.dark) .hover\:text-gray-200:hover   { color: #1C1A18; }
        html:not(.dark) .hover\:text-white:hover      { color: #000000; }
        /* blue accent slightly deeper on white bg */
        html:not(.dark) .text-blue-400                { color: #2563EB; }
        /* inputs */
        html:not(.dark) input.bg-gray-800,
        html:not(.dark) select.bg-gray-800,
        html:not(.dark) textarea.bg-gray-800          { background-color: #F8F4EF; }
        /* code mirror */
        html:not(.dark) .cm-editor                    { background: #FFFFFF !important; }
        html:not(.dark) .cm-editor .cm-gutters        { background: #F5F1EB !important; border-right: 1px solid #E5DFD7 !important; }
        /* table header sticky */
        html:not(.dark) .results-table th             { background: #FFFFFF; }
        /* spinner panel */
        html:not(.dark) #lb-spinner > div             { background-color: #FFFFFF; border-color: #E5DFD7; }
    </style>

    @stack('head')
</head>
<body class="bg-gray-950 text-gray-100 h-full" hx-boost="false">

    {{-- Global loading spinner --}}
    <div id="lb-spinner" style="display:none"
         class="fixed inset-0 z-[9999] flex items-center justify-center pointer-events-none">
        <div class="flex items-center gap-2.5 bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 shadow-xl">
            <svg class="lb-spin w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                        stroke-dasharray="32" stroke-dashoffset="10" stroke-linecap="round"/>
            </svg>
            <span class="text-sm text-gray-300">Loading…</span>
        </div>
    </div>

    {{-- Theme toggle — fixed pill, bottom-right ─────────────────────── --}}
    <button id="theme-toggle" onclick="toggleTheme()" title="Toggle theme"
        class="fixed bottom-5 right-5 z-[9998] flex items-center gap-1.5 px-3 py-1.5 rounded-full
               bg-gray-900 border border-gray-700 text-gray-400 hover:text-gray-200
               text-xs font-medium shadow-md transition-colors select-none">
        {{-- Sun: visible in dark mode (click → go light) --}}
        <svg id="lb-icon-sun" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:none">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
        </svg>
        {{-- Moon: visible in light mode (click → go dark) --}}
        <svg id="lb-icon-moon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
        </svg>
        <span id="lb-theme-label">Dark</span>
    </button>

    @yield('content')

    <script>
        // ── HTMX spinner ──────────────────────────────────────────────────
        (function () {
            var sp = document.getElementById('lb-spinner');
            var active = 0;
            function show() { active++; sp.style.display = 'flex'; }
            function hide() { active = Math.max(0, active - 1); if (active === 0) sp.style.display = 'none'; }
            document.body.addEventListener('htmx:beforeRequest', show);
            document.body.addEventListener('htmx:afterSettle',   hide);
            document.body.addEventListener('htmx:responseError', hide);
            document.body.addEventListener('htmx:sendError',     hide);
        })();

        // ── Theme toggle ──────────────────────────────────────────────────
        function _syncThemeUI() {
            var isDark  = document.documentElement.classList.contains('dark');
            document.getElementById('lb-icon-sun').style.display  = isDark ? '' : 'none';
            document.getElementById('lb-icon-moon').style.display = isDark ? 'none' : '';
            document.getElementById('lb-theme-label').textContent = isDark ? 'Light' : 'Dark';
        }

        function toggleTheme() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('lb-theme', isDark ? 'dark' : 'light');
            _syncThemeUI();
            if (typeof window.updateEditorTheme === 'function') {
                window.updateEditorTheme(isDark);
            }
        }

        // Sync icon state on load
        _syncThemeUI();
    </script>

    @stack('scripts')
    <script src="{{ asset('custom-htmx.js') }}"></script>
</body>
</html>
