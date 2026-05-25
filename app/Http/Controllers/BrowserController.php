<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\QueryHistory;
use App\Models\SavedQuery;
use App\Services\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Throwable;

class BrowserController extends Controller
{
    private function resolveActiveConnection(): Connection|RedirectResponse
    {
        $connectionId = session('larabased_connection_id');

        if (! $connectionId) {
            return redirect()->route('connections.index')->with('error', 'No active connection. Please connect first.');
        }

        $connection = Connection::where('id', $connectionId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $connection) {
            session()->forget('larabased_connection_id');

            return redirect()->route('connections.index')->with('error', 'Connection not found.');
        }

        return $connection;
    }

    public function index(): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        return view('browser.index', compact('connection'));
    }

    public function tables(DatabaseManager $manager): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        try {
            $tables = $manager->driver($connection)->getTables();
        } catch (Throwable $e) {
            return view('partials.driver-error', ['message' => $e->getMessage()]);
        }

        return view('partials.sidebar-tables', compact('tables'));
    }

    public function tableData(string $table, Request $request, DatabaseManager $manager): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $page = max(1, (int) $request->query('page', 1));
        $sortCol = $request->query('sort');
        $sortDir = strtoupper($request->query('dir', 'ASC')) === 'DESC' ? 'DESC' : 'ASC';
        $searchCol = $request->query('search_col') ?: null;
        $searchVal = $request->query('search_val') ?: null;
        $searchOp = $request->query('search_op', 'contains') ?: 'contains';

        try {
            $driver = $manager->driver($connection);
            $result = $driver->getRows($table, $page, 50, $sortCol ?: null, $sortDir, $searchCol, $searchVal, $searchOp);
            $pkColumns = $driver->getPrimaryKeyColumns($table);
            $colTypes = array_column($driver->getColumns($table), 'data_type', 'column_name');
        } catch (Throwable $e) {
            return view('partials.driver-error', ['message' => $e->getMessage()]);
        }

        return view('partials.table-data', [
            'table' => $table,
            'rows' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'perPage' => 50,
            'sortCol' => $sortCol,
            'sortDir' => $sortDir,
            'pkColumns' => $pkColumns,
            'colTypes' => $colTypes,
            'searchCol' => $searchCol,
            'searchVal' => $searchVal,
            'searchOp' => $searchOp,
        ]);
    }

    public function tableStructure(string $table, DatabaseManager $manager): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        try {
            $structure = $manager->driver($connection)->getTableStructure($table);
        } catch (Throwable $e) {
            return view('partials.driver-error', ['message' => $e->getMessage()]);
        }

        return view('partials.table-structure', [
            'table' => $table,
            'columns' => $structure['columns'],
            'indexes' => $structure['indexes'],
            'foreign_keys' => $structure['foreign_keys'],
        ]);
    }

    public function executeQuery(Request $request, DatabaseManager $manager): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $validated = $request->validate([
            'sql' => ['required', 'string'],
        ]);

        try {
            $result = $manager->driver($connection)->executeQuery($validated['sql']);
        } catch (Throwable $e) {
            $result = [
                'columns' => [],
                'rows' => [],
                'duration_ms' => 0,
                'affected' => 0,
                'error' => $e->getMessage(),
            ];
        }

        QueryHistory::create([
            'user_id' => auth()->id(),
            'connection_id' => $connection->id,
            'sql' => $validated['sql'],
            'duration_ms' => $result['duration_ms'],
            'error' => $result['error'],
            'executed_at' => now(),
        ]);

        return view('partials.query-results', [
            'columns' => $result['columns'],
            'rows' => $result['rows'],
            'duration_ms' => $result['duration_ms'],
            'affected' => $result['affected'],
            'error' => $result['error'],
            'sql' => $validated['sql'],
        ]);
    }

    public function savedQueries(): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $savedQueries = SavedQuery::where('user_id', auth()->id())
            ->where('connection_id', $connection->id)
            ->orderByDesc('created_at')
            ->get();

        return view('partials.saved-queries', compact('savedQueries'));
    }

    public function storeSavedQuery(Request $request): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sql' => ['required', 'string'],
        ]);

        SavedQuery::create([
            'user_id' => auth()->id(),
            'connection_id' => $connection->id,
            'name' => $validated['name'],
            'sql' => $validated['sql'],
        ]);

        $savedQueries = SavedQuery::where('user_id', auth()->id())
            ->where('connection_id', $connection->id)
            ->orderByDesc('created_at')
            ->get();

        return view('partials.saved-queries', compact('savedQueries'));
    }

    public function destroySavedQuery(SavedQuery $savedQuery): Response
    {
        abort_unless($savedQuery->user_id === auth()->id(), 403);

        $savedQuery->delete();

        return response('', 200);
    }

    public function history(): View|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $history = QueryHistory::where('user_id', auth()->id())
            ->where('connection_id', $connection->id)
            ->orderByDesc('executed_at')
            ->limit(50)
            ->get();

        return view('partials.query-history', compact('history'));
    }

    public function deleteRow(string $table, Request $request, DatabaseManager $manager): JsonResponse|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $validated = $request->validate([
            'pk' => ['required', 'array'],
        ]);

        try {
            $manager->driver($connection)->deleteRow($table, $validated['pk']);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function updateRow(string $table, Request $request, DatabaseManager $manager): JsonResponse|RedirectResponse
    {
        $connection = $this->resolveActiveConnection();

        if ($connection instanceof RedirectResponse) {
            return $connection;
        }

        $validated = $request->validate([
            'pk' => ['required', 'array'],
            'values' => ['required', 'array'],
            'null_columns' => ['array'],
        ]);

        try {
            $nullCols = $validated['null_columns'] ?? [];
            $values = $validated['values'];

            foreach ($nullCols as $col) {
                $values[$col] = null;
            }

            $manager->driver($connection)->updateRow($table, $validated['pk'], $values);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true]);
    }

    public function disconnect(): RedirectResponse
    {
        session()->forget('larabased_connection_id');

        return redirect()->route('connections.index');
    }
}
