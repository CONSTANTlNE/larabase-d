<div class="overflow-auto p-4 space-y-6">
    {{-- Columns --}}
    <div>
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Columns</h3>
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-800 bg-gray-900">
                        <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Name</th>
                        <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Type</th>
                        <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Nullable</th>
                        <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Default</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($columns as $col)
                        <tr class="border-b border-gray-800/50 hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-2.5 text-white font-mono">{{ $col['column_name'] }}</td>
                            <td class="px-4 py-2.5 text-blue-400 font-mono">{{ $col['data_type'] }}</td>
                            <td class="px-4 py-2.5">
                                @if($col['is_nullable'] === 'YES')
                                    <span class="text-yellow-400">nullable</span>
                                @else
                                    <span class="text-gray-500">not null</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5 text-gray-400 font-mono">
                                {{ $col['column_default'] ?? '' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Indexes --}}
    @if(!empty($indexes))
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Indexes</h3>
            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Name</th>
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Columns</th>
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($indexes as $index)
                            <tr class="border-b border-gray-800/50 hover:bg-gray-800 transition-colors">
                                <td class="px-4 py-2.5 text-white font-mono">{{ $index['index_name'] }}</td>
                                <td class="px-4 py-2.5 text-gray-300 font-mono">{{ $index['columns'] }}</td>
                                <td class="px-4 py-2.5">
                                    @if($index['is_primary'])
                                        <span class="text-yellow-400 text-xs">PRIMARY</span>
                                    @elseif($index['is_unique'])
                                        <span class="text-blue-400 text-xs">UNIQUE</span>
                                    @else
                                        <span class="text-gray-500 text-xs">INDEX</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Foreign Keys --}}
    @if(!empty($foreign_keys))
        <div>
            <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">Foreign Keys</h3>
            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
                <table class="w-full text-xs">
                    <thead>
                        <tr class="border-b border-gray-800">
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Constraint</th>
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">Column</th>
                            <th class="px-4 py-2.5 text-left text-gray-400 font-medium">References</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($foreign_keys as $fk)
                            <tr class="border-b border-gray-800/50 hover:bg-gray-800 transition-colors">
                                <td class="px-4 py-2.5 text-gray-300 font-mono">{{ $fk['constraint_name'] }}</td>
                                <td class="px-4 py-2.5 text-white font-mono">{{ $fk['column_name'] }}</td>
                                <td class="px-4 py-2.5 text-blue-400 font-mono">
                                    {{ $fk['foreign_table'] }}.{{ $fk['foreign_column'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
