@php
    /**
     * Cost-based node type coloring.
     */
    function _explainNodeColor(string $type): string
    {
        return match (true) {
            str_contains($type, 'Seq Scan')     => 'text-amber-400',
            str_contains($type, 'Index Scan')   => 'text-green-400',
            str_contains($type, 'Index Only')   => 'text-emerald-400',
            str_contains($type, 'Bitmap')       => 'text-teal-400',
            str_contains($type, 'Hash Join')    => 'text-blue-400',
            str_contains($type, 'Merge Join')   => 'text-cyan-400',
            str_contains($type, 'Nested Loop')  => 'text-purple-400',
            str_contains($type, 'Hash')         => 'text-indigo-400',
            str_contains($type, 'Sort')         => 'text-orange-400',
            str_contains($type, 'Aggregate')    => 'text-pink-400',
            str_contains($type, 'Limit')        => 'text-gray-400',
            default                             => 'text-gray-300',
        };
    }

    /**
     * Recursively renders a plan node as an HTML string.
     */
    function _explainRenderNode(array $node, int $depth = 0): string
    {
        $type        = $node['Node Type'] ?? 'Unknown';
        $relation    = $node['Relation Name'] ?? null;
        $alias       = isset($node['Alias']) && $node['Alias'] !== $relation ? $node['Alias'] : null;
        $startCost   = number_format((float) ($node['Startup Cost'] ?? 0), 2);
        $totalCost   = number_format((float) ($node['Total Cost'] ?? 0), 2);
        $planRows    = number_format((int) ($node['Plan Rows'] ?? 0));
        $planWidth   = $node['Plan Width'] ?? 0;
        $children    = $node['Plans'] ?? [];
        $filter      = $node['Filter'] ?? $node['Index Cond'] ?? $node['Hash Cond'] ?? $node['Join Filter'] ?? null;
        $nodeColor   = _explainNodeColor($type);

        $label  = "<span class=\"font-semibold {$nodeColor}\">{$type}</span>";
        if ($relation) {
            $label .= " <span class=\"text-gray-500\">on</span> <span class=\"text-gray-200 font-mono\">{$relation}</span>";
        }
        if ($alias) {
            $label .= " <span class=\"text-gray-600\">(alias: {$alias})</span>";
        }

        $costBadge = "<span class=\"text-gray-600\">cost=</span><span class=\"text-yellow-500/80\">{$startCost}..{$totalCost}</span>"
            . " <span class=\"text-gray-600\">rows=</span><span class=\"text-sky-400/80\">{$planRows}</span>"
            . " <span class=\"text-gray-600\">width=</span><span class=\"text-gray-500\">{$planWidth}</span>";

        $open = $depth < 2 ? ' open' : '';
        $indent = str_repeat('  ', $depth);

        $html = "<details{$open} class=\"plan-node\">"
            . "<summary class=\"list-none cursor-pointer select-none flex flex-wrap items-baseline gap-2 py-0.5 hover:bg-gray-800/40 rounded px-1 -mx-1\">"
            . "<span class=\"text-gray-600 font-mono text-[10px] flex-shrink-0\">" . str_repeat('│ ', $depth) . "├─</span>"
            . "{$label}"
            . "<span class=\"text-[10px] ml-1\">{$costBadge}</span>"
            . "</summary>";

        if ($filter) {
            $html .= "<div class=\"pl-4 text-[10px] text-gray-500 py-0.5 font-mono\"><span class=\"text-gray-600\">cond: </span>"
                . "<span class=\"text-gray-400\">" . htmlspecialchars($filter) . "</span></div>";
        }

        foreach ($children as $child) {
            $html .= _explainRenderNode($child, $depth + 1);
        }

        $html .= "</details>";

        return $html;
    }
@endphp

<div class="px-4 py-3 font-mono text-xs select-text">

    {{-- SQL summary --}}
    <div class="mb-3 pb-2 border-b border-gray-800 flex items-start gap-2">
        <span class="text-gray-600 flex-shrink-0 mt-0.5">EXPLAIN</span>
        <span class="text-gray-400 break-all leading-relaxed">{{ Str::limit($sql, 200) }}</span>
    </div>

    @if(empty($plan))
        <p class="text-gray-500">No plan returned.</p>
    @else
        {{-- Cost overview --}}
        @php
            $totalCost  = number_format((float) ($plan['Total Cost'] ?? 0), 2);
            $planRows   = number_format((int) ($plan['Plan Rows'] ?? 0));
            $nodeType   = $plan['Node Type'] ?? 'Unknown';
        @endphp
        <div class="flex items-center gap-4 mb-3 px-2 py-1.5 bg-gray-800/40 rounded text-[11px]">
            <span class="text-gray-500">Total cost: <span class="text-yellow-400 font-medium">{{ $totalCost }}</span></span>
            <span class="text-gray-500">Est. rows: <span class="text-sky-400 font-medium">{{ $planRows }}</span></span>
            <span class="text-gray-500">Root: <span class="font-medium {{ _explainNodeColor($nodeType) }}">{{ $nodeType }}</span></span>
        </div>

        {{-- Plan tree --}}
        <div class="plan-tree space-y-0.5 leading-5">
            {!! _explainRenderNode($plan) !!}
        </div>
    @endif
</div>

<style>
    .plan-node > summary::-webkit-details-marker { display: none; }
    .plan-node > summary::marker { display: none; }
</style>
