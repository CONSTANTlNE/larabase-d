<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-white">Slow Queries</h2>
            <p class="text-xs text-gray-500 mt-0.5">Top queries by average execution time — via <span class="font-mono">pg_stat_statements</span></p>
        </div>
        @if($available)
            <span class="text-[10px] text-green-400 bg-green-500/10 px-2 py-1 rounded">Extension active</span>
        @endif
    </div>

    @if(!$available)
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-12 h-12 rounded-full bg-amber-500/10 flex items-center justify-center mb-3">
                <svg class="w-6 h-6 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
            </div>
            <p class="text-sm text-gray-400 font-medium">pg_stat_statements not enabled</p>
            <p class="text-xs text-gray-600 mt-1 max-w-sm">Add <span class="font-mono text-gray-400">pg_stat_statements</span> to <span class="font-mono text-gray-400">shared_preload_libraries</span> in postgresql.conf, restart, then run:</p>
            <code class="mt-2 text-xs text-blue-300 bg-gray-800 px-3 py-1.5 rounded font-mono">CREATE EXTENSION pg_stat_statements;</code>
        </div>
    @elseif(empty($rows))
        <div class="flex items-center justify-center py-16 text-gray-500 text-sm">No statements recorded yet.</div>
    @else
        <div class="overflow-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="px-3 py-2.5 text-gray-500 font-medium w-12 text-right">#</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium">Query</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Calls</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Avg (ms)</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Total (ms)</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Rows</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Cache hit</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $i => $row)
                        @php
                            $avgMs = (float) $row['mean_ms'];
                            $avgColor = match(true) {
                                $avgMs >= 1000 => 'text-red-400',
                                $avgMs >= 100  => 'text-amber-400',
                                $avgMs >= 10   => 'text-yellow-400',
                                default        => 'text-green-400',
                            };
                            $cacheHit = $row['cache_hit_pct'] !== null ? (float) $row['cache_hit_pct'] : null;
                            $cacheColor = match(true) {
                                $cacheHit === null => 'text-gray-500',
                                $cacheHit >= 99   => 'text-green-400',
                                $cacheHit >= 90   => 'text-yellow-400',
                                default           => 'text-red-400',
                            };
                            $sql = trim(preg_replace('/\s+/', ' ', $row['query'] ?? ''));
                        @endphp
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800/30 group">
                            <td class="px-3 py-2 text-gray-600 text-right font-mono">{{ $i + 1 }}</td>
                            <td class="px-3 py-2 max-w-md">
                                <span class="font-mono text-gray-300 leading-relaxed break-all">{{ Str::limit($sql, 120) }}</span>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-400 font-mono">{{ number_format((int) $row['calls']) }}</td>
                            <td class="px-3 py-2 text-right font-mono font-medium {{ $avgColor }}">{{ $row['mean_ms'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-500 font-mono">{{ $row['total_ms'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-500 font-mono">{{ number_format((int) $row['rows']) }}</td>
                            <td class="px-3 py-2 text-right font-mono {{ $cacheColor }}">
                                {{ $cacheHit !== null ? $cacheHit . '%' : '—' }}
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button
                                    class="opacity-0 group-hover:opacity-100 text-[10px] text-blue-400 hover:text-blue-300 border border-blue-500/30 px-1.5 py-0.5 rounded transition-all"
                                    data-restore-sql="{{ $sql }}"
                                    title="Load in editor"
                                >
                                    Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
