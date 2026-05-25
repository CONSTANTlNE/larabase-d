<div class="bg-gray-900 border border-blue-500/30 rounded-xl p-6 mb-6">
    <div class="flex items-center justify-between mb-5">
        <h3 class="font-semibold text-white">New Connection</h3>
        <button
            hx-get="{{ route('connections.index') }}"
            hx-target="#new-connection-form"
            hx-swap="innerHTML"
            class="text-gray-500 hover:text-gray-300 transition-colors"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <form
        id="new-conn-form"
        hx-post="{{ route('connections.store') }}"
        hx-target="#new-connection-form"
        hx-swap="innerHTML"
        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
    >
        @csrf
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Connection Name</label>
                <input type="text" name="name" required placeholder="My Production DB"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Host</label>
                <input type="text" name="host" required placeholder="localhost"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Port</label>
                <input type="number" name="port" value="5432" required min="1" max="65535"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Database</label>
                <input type="text" name="database" required placeholder="postgres"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Username</label>
                <input type="text" name="username" required placeholder="postgres"
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-400 mb-1.5">Password</label>
                <input type="password" name="password" required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-sm text-gray-100 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-3 col-span-2">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="ssl" value="1" class="sr-only peer">
                    <div class="w-9 h-5 bg-gray-700 rounded-full peer peer-checked:bg-blue-600 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-4"></div>
                    <span class="ml-2 text-sm text-gray-400">Use SSL</span>
                </label>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-5">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-500 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                Save Connection
            </button>
            <button type="button" id="test-new-conn"
                class="text-sm text-gray-400 hover:text-gray-200 border border-gray-700 hover:border-gray-600 px-4 py-2 rounded-lg transition-colors">
                Test Connection
            </button>
            <span id="new-conn-test-result" class="text-xs"></span>
        </div>
    </form>
</div>

<script>
    document.getElementById('test-new-conn').addEventListener('click', async function() {
        const form = document.getElementById('new-conn-form');
        const data = new FormData(form);
        const resultEl = document.getElementById('new-conn-test-result');
        resultEl.textContent = 'Testing...';
        resultEl.className = 'text-xs text-gray-400';

        // We need to create a temporary connection for testing
        // Instead, just validate form and show partial feedback
        const host = data.get('host');
        const port = data.get('port');
        const db = data.get('database');
        if (!host || !port || !db) {
            resultEl.textContent = 'Fill in host, port, and database first.';
            resultEl.className = 'text-xs text-yellow-400';
            return;
        }
        resultEl.textContent = 'Save first to test connection.';
        resultEl.className = 'text-xs text-gray-400';
    });
</script>
