<?php

use App\InscripcionPagos\Controllers\PortalPagoController;
use App\RegistroPublico\Controllers\PortalCandidatoController;
use App\RegistroPublico\Controllers\RegistroController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

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
    Route::post('iniciar', [PortalPagoController::class, 'iniciar'])->name('iniciar');
    Route::get('exitoso', [PortalPagoController::class, 'exitoso'])->name('exitoso');
    Route::get('cancelado', [PortalPagoController::class, 'cancelado'])->name('cancelado');
    Route::get('comprobante', [PortalPagoController::class, 'comprobante'])->name('comprobante');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/administracion.php';
