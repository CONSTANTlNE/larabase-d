@php
    $warningCount = count(array_filter($rows, fn($r) => (float)($r['bloat_pct'] ?? 0) >= 10));
@endphp

<div class="p-4">
    <div class="flex items-center justify-between mb-4">
        <div>
            <h2 class="text-sm font-semibold text-white">Table Bloat</h2>
            <p class="text-xs text-gray-500 mt-0.5">Dead tuples and vacuum status — via <span class="font-mono">pg_stat_user_tables</span></p>
        </div>
        @if($warningCount > 0)
            <span class="text-[10px] text-amber-400 bg-amber-500/10 px-2 py-1 rounded">
                {{ $warningCount }} table{{ $warningCount !== 1 ? 's' : '' }} need VACUUM
            </span>
        @else
            <span class="text-[10px] text-green-400 bg-green-500/10 px-2 py-1 rounded">All tables healthy</span>
        @endif
    </div>

    @if(empty($rows))
        <div class="flex items-center justify-center py-16 text-gray-500 text-sm">No user tables found.</div>
    @else
        {{-- Warning banner --}}
        @if($warningCount > 0)
            <div class="mb-3 flex items-center gap-2 text-xs text-amber-300 bg-amber-500/10 border border-amber-500/20 rounded px-3 py-2">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                </svg>
                Tables with ≥10% dead tuples are highlighted. Run <code class="font-mono text-amber-200 ml-1">VACUUM ANALYZE schema.table</code> to reclaim space.
            </div>
        @endif

        <div class="overflow-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="border-b border-gray-800">
                        <th class="px-3 py-2.5 text-gray-500 font-medium">Table</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Live rows</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Dead rows</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Bloat</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium text-right whitespace-nowrap">Size</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium whitespace-nowrap">Last vacuum</th>
                        <th class="px-3 py-2.5 text-gray-500 font-medium whitespace-nowrap">Last analyze</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        @php
                            $bloat    = (float) ($row['bloat_pct'] ?? 0);
                            $deadTup  = (int)   ($row['n_dead_tup'] ?? 0);
                            $isWarn   = $bloat >= 10;
                            $isCrit   = $bloat >= 30;

                            $bloatColor = match(true) {
                                $bloat >= 30 => 'text-red-400 font-semibold',
                                $bloat >= 10 => 'text-amber-400 font-medium',
                                $bloat >= 5  => 'text-yellow-500',
                                default      => 'text-green-400',
                            };

                            $vacuumDisplay = fn(?string $dt) => $dt
                                ? \Carbon\Carbon::parse($dt)->diffForHumans()
                                : '—';

                            $qualifiedName = $row['schemaname'] . '.' . $row['table_name'];
                            $lastVacuum  = $row['last_vacuum']      ?? $row['last_autovacuum']    ?? null;
                            $lastAnalyze = $row['last_analyze']     ?? $row['last_autoanalyze']   ?? null;
                        @endphp
                        <tr class="border-b border-gray-800/50 {{ $isWarn ? 'bg-amber-500/5' : 'hover:bg-gray-800/30' }} group">
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2">
                                    @if($isCrit)
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span>
                                    @elseif($isWarn)
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 flex-shrink-0"></span>
                                    @else
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500/50 flex-shrink-0"></span>
                                    @endif
                                    <div>
                                        <span class="font-mono text-gray-300">{{ $row['table_name'] }}</span>
                                        @if($row['schemaname'] !== 'public')
                                            <span class="text-gray-600 ml-1">{{ $row['schemaname'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-400 font-mono">{{ number_format((int) $row['n_live_tup']) }}</td>
                            <td class="px-3 py-2 text-right font-mono {{ $deadTup > 0 ? 'text-amber-400/80' : 'text-gray-600' }}">
                                {{ $deadTup > 0 ? number_format($deadTup) : '0' }}
                            </td>
                            <td class="px-3 py-2 text-right font-mono">
                                <div class="flex items-center justify-end gap-1.5">
                                    {{-- Bloat bar --}}
                                    <div class="w-12 h-1.5 bg-gray-800 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full {{ $isCrit ? 'bg-red-500' : ($isWarn ? 'bg-amber-500' : 'bg-green-500/60') }}"
                                             style="width: {{ min(100, $bloat) }}%"></div>
                                    </div>
                                    <span class="{{ $bloatColor }}">{{ $bloat }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right text-gray-500 font-mono">{{ $row['total_size'] ?? '—' }}</td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                <div class="flex flex-col gap-0.5">
                                    @if($row['last_vacuum'])
                                        <span title="{{ $row['last_vacuum'] }}">{{ $vacuumDisplay($row['last_vacuum']) }}</span>
                                    @endif
                                    @if($row['last_autovacuum'] && !$row['last_vacuum'])
                                        <span class="text-gray-700" title="{{ $row['last_autovacuum'] }}">auto: {{ $vacuumDisplay($row['last_autovacuum']) }}</span>
                                    @elseif($row['last_autovacuum'])
                                        <span class="text-gray-700" title="{{ $row['last_autovacuum'] }}">auto: {{ $vacuumDisplay($row['last_autovacuum']) }}</span>
                                    @endif
                                    @if(!$row['last_vacuum'] && !$row['last_autovacuum'])
                                        <span>Never</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                @if($lastAnalyze)
                                    <span title="{{ $lastAnalyze }}">{{ $vacuumDisplay($lastAnalyze) }}</span>
                                @else
                                    Never
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
