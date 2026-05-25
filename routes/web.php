<?php

use App\Http\Controllers\BrowserController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

// Setup — first-run user creation
Route::get('/setup', [SetupController::class, 'show'])->name('setup');
Route::post('/setup', [SetupController::class, 'store'])->name('setup.store');

// Root redirect
Route::get('/', fn () => redirect()->route('connections.index'))->middleware('auth');

Route::middleware('auth')->group(function () {
    // Connections
    Route::get('/connections', [ConnectionController::class, 'index'])->name('connections.index');
    Route::post('/connections', [ConnectionController::class, 'store'])->name('connections.store');
    Route::delete('/connections/{connection}', [ConnectionController::class, 'destroy'])->name('connections.destroy');
    Route::post('/connections/{connection}/test', [ConnectionController::class, 'test'])->name('connections.test');
    Route::get('/connections/{connection}/connect', [ConnectionController::class, 'connect'])->name('connections.connect');

    // Browser
    Route::get('/browser/disconnect', [BrowserController::class, 'disconnect'])->name('browser.disconnect');
    Route::get('/browser', [BrowserController::class, 'index'])->name('browser');
    Route::get('/browser/tables', [BrowserController::class, 'tables'])->name('browser.tables');
    Route::get('/browser/tables/{table}', [BrowserController::class, 'tableData'])->name('browser.table-data');
    Route::get('/browser/tables/{table}/structure', [BrowserController::class, 'tableStructure'])->name('browser.table-structure');
    Route::get('/browser/tables/{table}/relations', [BrowserController::class, 'tableRelations'])->name('browser.table-relations');
    Route::delete('/browser/tables/{table}/rows/bulk', [BrowserController::class, 'deleteRows'])->name('browser.rows.delete');
    Route::delete('/browser/tables/{table}/rows', [BrowserController::class, 'deleteRow'])->name('browser.row.delete');
    Route::patch('/browser/tables/{table}/rows', [BrowserController::class, 'updateRow'])->name('browser.row.update');
    Route::delete('/browser/tables/{table}/records', [BrowserController::class, 'truncateTable'])->name('browser.table.truncate');
    Route::delete('/browser/tables/{table}', [BrowserController::class, 'dropTable'])->name('browser.table.drop');
    Route::post('/browser/query', [BrowserController::class, 'executeQuery'])->name('browser.query');
    Route::post('/browser/explain', [BrowserController::class, 'explainQuery'])->name('browser.explain');
    Route::get('/browser/pg-stat-statements', [BrowserController::class, 'pgStatStatements'])->name('browser.pg-stat-statements');
    Route::get('/browser/table-bloat', [BrowserController::class, 'tableBloat'])->name('browser.table-bloat');
    Route::get('/browser/extensions', [BrowserController::class, 'extensions'])->name('browser.extensions');
    Route::get('/browser/saved-queries', [BrowserController::class, 'savedQueries'])->name('browser.saved-queries');
    Route::post('/browser/saved-queries', [BrowserController::class, 'storeSavedQuery'])->name('browser.saved-queries.store');
    Route::delete('/browser/saved-queries/{savedQuery}', [BrowserController::class, 'destroySavedQuery'])->name('browser.saved-queries.destroy');
    Route::get('/browser/history', [BrowserController::class, 'history'])->name('browser.history');
});
