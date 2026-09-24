<?php

use App\Http\Controllers\DownloadPlanAlimentarioController;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Private attachment boundary: the route resolves records only (consultation
// binding), ownership is enforced through the plan relationship, and access is
// limited to authenticated panel users.
Route::get('{consulta}/plan-alimentario/download', DownloadPlanAlimentarioController::class)
    ->middleware(FilamentAuthenticate::class)
    ->name('plan-alimentario.download');
