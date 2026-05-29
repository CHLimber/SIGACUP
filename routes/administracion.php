<?php

use App\AdministracionSistema\Controllers\GestionController;
use App\Admision\Controllers\AdmisionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('administracion')->group(function () {
    Route::resource('gestiones', GestionController::class)
        ->except(['show'])
        ->parameters(['gestiones' => 'gestion']);
    Route::patch('gestiones/{gestion}/avanzar', [GestionController::class, 'avanzar'])->name('gestiones.avanzar');

    Route::middleware('role:administrador,coordinador')->prefix('admision')->name('admision.')->group(function () {
        Route::get('/', [AdmisionController::class, 'index'])->name('index');

        Route::get('candidato-estudiante/{candidato}', [AdmisionController::class, 'revisarCandidatoEstudiante'])->name('candidato-estudiante.revisar');
        Route::patch('candidato-estudiante/{candidato}/aprobar', [AdmisionController::class, 'aprobarCandidatoEstudiante'])->name('candidato-estudiante.aprobar');
        Route::patch('candidato-estudiante/{candidato}/rechazar', [AdmisionController::class, 'rechazarCandidatoEstudiante'])->name('candidato-estudiante.rechazar');
        Route::patch('candidato-estudiante/{candidato}/solicitar-correcciones', [AdmisionController::class, 'solicitarCorreccionesEstudiante'])->name('candidato-estudiante.solicitar-correcciones');

        Route::get('candidato-docente/{candidato}', [AdmisionController::class, 'revisarCandidatoDocente'])->name('candidato-docente.revisar');
        Route::patch('candidato-docente/{candidato}/aprobar', [AdmisionController::class, 'aprobarCandidatoDocente'])->name('candidato-docente.aprobar');
        Route::patch('candidato-docente/{candidato}/rechazar', [AdmisionController::class, 'rechazarCandidatoDocente'])->name('candidato-docente.rechazar');
        Route::patch('candidato-docente/{candidato}/solicitar-correcciones', [AdmisionController::class, 'solicitarCorreccionesDocente'])->name('candidato-docente.solicitar-correcciones');

        Route::patch('requisitos/{archivo}/aprobar', [AdmisionController::class, 'aprobarRequisito'])->name('requisitos.aprobar');
        Route::patch('requisitos/{archivo}/rechazar', [AdmisionController::class, 'rechazarRequisito'])->name('requisitos.rechazar');
        Route::get('requisitos/{archivo}/descargar', [AdmisionController::class, 'descargarRequisito'])->name('requisitos.descargar');
    });
});
