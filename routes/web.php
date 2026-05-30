<?php

use App\AdministracionSistema\Models\Carrera;
use App\InscripcionPagos\Controllers\PortalPagoController;
use App\RegistroPublico\Controllers\PortalCandidatoController;
use App\RegistroPublico\Controllers\RegistroController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre']),
    ]);
})->name('home');

Route::post('/registro/estudiante', [RegistroController::class, 'storeCandidatoEstudiante'])->name('registro.estudiante');
Route::post('/registro/docente', [RegistroController::class, 'storeCandidatoDocente'])->name('registro.docente');

Route::prefix('candidato/{token}')->name('portal.candidato.')->group(function () {
    Route::get('requisitos', [PortalCandidatoController::class, 'show'])->name('show');
    Route::post('requisitos/{codigo}', [PortalCandidatoController::class, 'subir'])->name('subir');
    Route::delete('requisitos/{codigo}', [PortalCandidatoController::class, 'eliminar'])->name('eliminar');
    Route::get('requisitos/{codigo}/descargar', [PortalCandidatoController::class, 'descargar'])->name('descargar');
    Route::post('enviar', [PortalCandidatoController::class, 'enviar'])->name('enviar');
});

Route::prefix('matricula/{token}')->name('portal.matricula.')->group(function () {
    Route::get('/', [PortalPagoController::class, 'show'])->name('show');
    Route::post('payment-intent', [PortalPagoController::class, 'crearPaymentIntent'])->name('payment-intent');
    Route::post('confirmar', [PortalPagoController::class, 'confirmar'])->name('confirmar');
    Route::get('comprobante', [PortalPagoController::class, 'comprobante'])->name('comprobante');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/administracion.php';
