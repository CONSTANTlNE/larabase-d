<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Services\DatabaseManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ConnectionController extends Controller
{
    public function index(): View
    {
        $connections = Connection::where('user_id', auth()->id())
            ->orderBy('name')
            ->get();

        return view('connections.index', compact('connections'));
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'ssl' => ['boolean'],
        ]);

        Connection::create([
            ...$validated,
            'user_id' => auth()->id(),
            'password' => encrypt($validated['password']),
            'ssl' => $request->boolean('ssl'),
        ]);

        // HTMX: trigger a full page redirect after save
        if ($request->hasHeader('HX-Request')) {
            return response('', 200)
                ->header('HX-Redirect', route('connections.index'));
        }

        return redirect()->route('connections.index')->with('success', 'Connection saved.');
    }

    public function destroy(Connection $connection): Response
    {
        abort_unless($connection->user_id === auth()->id(), 403);

        $connection->delete();

        return response('', 200);
    }

    public function test(Connection $connection, DatabaseManager $manager): JsonResponse
    {
        abort_unless($connection->user_id === auth()->id(), 403);

        $result = $manager->driver($connection)->testConnection();

        return response()->json($result);
    }

    public function connect(Connection $connection): RedirectResponse
    {
        abort_unless($connection->user_id === auth()->id(), 403);

        session()->put('larabased_connection_id', $connection->id);

        return redirect()->route('browser');
    }
}
