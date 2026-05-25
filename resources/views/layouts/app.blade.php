<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LaraBase-D')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'gray-950': '#030712',
                    }
                }
            }
        }
    </script>
    <script src="https://unpkg.com/htmx.org@2.0.3"></script>
    <style>
        /* Override HTMX default opacity-based indicator with display-based */
        .htmx-indicator { display: none !important; }
        .htmx-request > .htmx-indicator,
        .htmx-request.htmx-indicator { display: flex !important; }

        @keyframes spin { to { transform: rotate(360deg); } }
        .lb-spin { animation: spin 0.7s linear infinite; }
    </style>
    @stack('head')
</head>
<body class="bg-gray-950 text-gray-100 h-full" hx-boost="false">

    {{-- Global loading spinner — fixed overlay, lives OUTSIDE all HTMX swap targets --}}
    <div id="lb-spinner" style="display:none"
         class="fixed inset-0 z-[9999] flex items-center justify-center pointer-events-none">
        <div class="flex items-center gap-2.5 bg-gray-900 border border-gray-800 rounded-xl px-4 py-3 shadow-2xl">
            <svg class="lb-spin w-4 h-4 text-blue-500" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"
                        stroke-dasharray="32" stroke-dashoffset="10" stroke-linecap="round"/>
            </svg>
            <span class="text-sm text-gray-300">Loading…</span>
        </div>
    </div>

    @yield('content')

    <script>
        // Show spinner on every HTMX request; hide on settle or error
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
    </script>

    @stack('scripts')
<script src="{{asset('custom-htmx.js')}}"></script>
</body>
</html>
