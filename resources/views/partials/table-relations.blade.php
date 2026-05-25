@php
    $tableName = str_contains($table, '.') ? explode('.', $table, 2)[1] : $table;
    $outCount  = count($outgoing);
    $inCount   = count($incoming);

    $ruleColor = fn(string $rule): string => match (strtoupper($rule)) {
        'CASCADE'    => 'text-green-400',
        'RESTRICT'   => 'text-orange-400',
        'SET NULL'   => 'text-yellow-400',
        'SET DEFAULT'=> 'text-sky-400',
        default      => 'text-gray-500',  // NO ACTION
    };
@endphp

{{--
    Column pill semantics (consistent across every card):
      Amber + chain icon  =  the FK column  (the side that HAS the constraint)
      Teal  + key  icon   =  the referenced column  (the PK / target side)
--}}

<div class="p-5 space-y-7">

    {{-- Summary ──────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs font-mono font-semibold text-white">{{ $tableName }}</span>
        @if($outCount === 0 && $inCount === 0)
            <span class="text-xs text-gray-500">— no FK relationships</span>
        @else
            @if($outCount > 0)
                <span class="inline-flex items-center gap-1 text-[10px] text-amber-400 bg-amber-500/10 border border-amber-500/20 px-2 py-0.5 rounded-full">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                    </svg>
                    {{ $outCount }} reference{{ $outCount !== 1 ? 's' : '' }}
                </span>
            @endif
            @if($inCount > 0)
                <span class="inline-flex items-center gap-1 text-[10px] text-teal-400 bg-teal-500/10 border border-teal-500/20 px-2 py-0.5 rounded-full">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                    </svg>
                    referenced by {{ $inCount }}
                </span>
            @endif
        @endif
    </div>

    {{-- OUTGOING (this table → others) ──────────────────────────────────── --}}
    @if($outCount > 0)
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
                References
                <span class="text-gray-600 font-normal normal-case tracking-normal">— this table points to</span>
            </h3>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                @foreach($outgoing as $fk)
                    @php $foreignQualified = $fk['foreign_schema'].'.'.$fk['foreign_table']; @endphp
                    <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">

                        {{-- Constraint name --}}
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-800 bg-gray-800/25">
                            <span class="text-xs font-mono text-gray-300 truncate pr-2">{{ $fk['constraint_name'] }}</span>
                            <span class="text-[10px] font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded flex-shrink-0">FKEY</span>
                        </div>

                        {{-- Role labels --}}
                        <div class="flex items-center justify-between px-4 pt-3 pb-0">
                            <div class="flex items-center gap-1 text-[10px] text-amber-500/70 font-medium">
                                {{-- chain / link icon --}}
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                FK column
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-teal-500/70 font-medium">
                                References
                                {{-- key icon --}}
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Diagram row --}}
                        <div class="flex items-center gap-3 px-4 py-3">
                            {{-- FK side (this table — amber) --}}
                            <div class="flex-1 min-w-0">
                                <div class="text-[10px] text-gray-600 font-mono mb-1.5 truncate">{{ $table }}</div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($fk['columns'] as $col)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-mono
                                                     bg-amber-500/10 text-amber-300 rounded border border-amber-500/25">
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            {{ $col }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="flex-shrink-0 flex items-center gap-0.5 text-gray-600">
                                <span class="w-4 h-px bg-gray-700 block"></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            {{-- Referenced side (foreign table — teal, clickable) --}}
                            <div class="flex-1 min-w-0 text-right">
                                <button
                                    onclick="selectTable('{{ $foreignQualified }}'); activateTab('data')"
                                    class="text-[10px] font-mono text-teal-400 hover:text-teal-300 transition-colors mb-1.5 truncate block w-full text-right hover:underline"
                                    title="Open {{ $foreignQualified }}">
                                    {{ $foreignQualified }}
                                </button>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    @foreach($fk['foreign_columns'] as $col)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-mono
                                                     bg-teal-500/10 text-teal-300 rounded border border-teal-500/25">
                                            {{ $col }}
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Referential actions --}}
                        <div class="flex items-center gap-5 px-4 py-2 border-t border-gray-800/50 bg-gray-800/15">
                            <span class="text-[10px] text-gray-600 uppercase tracking-wide">
                                DELETE <span class="ml-1 font-semibold {{ $ruleColor($fk['delete_rule']) }} normal-case tracking-normal">{{ $fk['delete_rule'] }}</span>
                            </span>
                            <span class="text-[10px] text-gray-600 uppercase tracking-wide">
                                UPDATE <span class="ml-1 font-semibold {{ $ruleColor($fk['update_rule']) }} normal-case tracking-normal">{{ $fk['update_rule'] }}</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- INCOMING (others → this table) ──────────────────────────────────── --}}
    @if($inCount > 0)
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3 flex items-center gap-2">
                <svg class="w-3.5 h-3.5 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16l-4-4m0 0l4-4m-4 4h18"/>
                </svg>
                Referenced By
                <span class="text-gray-600 font-normal normal-case tracking-normal">— other tables point here</span>
            </h3>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-3">
                @foreach($incoming as $fk)
                    @php $refQualified = $fk['referencing_schema'].'.'.$fk['referencing_table']; @endphp
                    <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">

                        {{-- Constraint name --}}
                        <div class="flex items-center justify-between px-4 py-2.5 border-b border-gray-800 bg-gray-800/25">
                            <span class="text-xs font-mono text-gray-300 truncate pr-2">{{ $fk['constraint_name'] }}</span>
                            <span class="text-[10px] font-semibold text-amber-400 bg-amber-500/10 border border-amber-500/20 px-1.5 py-0.5 rounded flex-shrink-0">FKEY</span>
                        </div>

                        {{-- Role labels --}}
                        <div class="flex items-center justify-between px-4 pt-3 pb-0">
                            <div class="flex items-center gap-1 text-[10px] text-amber-500/70 font-medium">
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                FK column
                            </div>
                            <div class="flex items-center gap-1 text-[10px] text-teal-500/70 font-medium">
                                References
                                <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                </svg>
                            </div>
                        </div>

                        {{-- Diagram row --}}
                        <div class="flex items-center gap-3 px-4 py-3">
                            {{-- FK side (referencing table — amber, clickable) --}}
                            <div class="flex-1 min-w-0">
                                <button
                                    onclick="selectTable('{{ $refQualified }}'); activateTab('data')"
                                    class="text-[10px] font-mono text-amber-400 hover:text-amber-300 transition-colors mb-1.5 truncate block w-full text-left hover:underline"
                                    title="Open {{ $refQualified }}">
                                    {{ $refQualified }}
                                </button>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($fk['referencing_columns'] as $col)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-mono
                                                     bg-amber-500/10 text-amber-300 rounded border border-amber-500/25">
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                            </svg>
                                            {{ $col }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Arrow --}}
                            <div class="flex-shrink-0 flex items-center gap-0.5 text-gray-600">
                                <span class="w-4 h-px bg-gray-700 block"></span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>

                            {{-- Referenced side (this table — teal) --}}
                            <div class="flex-1 min-w-0 text-right">
                                <div class="text-[10px] text-gray-600 font-mono mb-1.5 truncate">{{ $table }}</div>
                                <div class="flex flex-wrap gap-1 justify-end">
                                    @foreach($fk['columns'] as $col)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-mono
                                                     bg-teal-500/10 text-teal-300 rounded border border-teal-500/25">
                                            {{ $col }}
                                            <svg class="w-2.5 h-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                                            </svg>
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- Referential actions --}}
                        <div class="flex items-center gap-5 px-4 py-2 border-t border-gray-800/50 bg-gray-800/15">
                            <span class="text-[10px] text-gray-600 uppercase tracking-wide">
                                DELETE <span class="ml-1 font-semibold {{ $ruleColor($fk['delete_rule']) }} normal-case tracking-normal">{{ $fk['delete_rule'] }}</span>
                            </span>
                            <span class="text-[10px] text-gray-600 uppercase tracking-wide">
                                UPDATE <span class="ml-1 font-semibold {{ $ruleColor($fk['update_rule']) }} normal-case tracking-normal">{{ $fk['update_rule'] }}</span>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Empty state --}}
    @if($outCount === 0 && $inCount === 0)
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="w-8 h-8 text-gray-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
            </svg>
            <p class="text-sm text-gray-500">No foreign key relationships</p>
            <p class="text-xs text-gray-600 mt-1">This table has no FK constraints defined.</p>
        </div>
    @endif
</div>
