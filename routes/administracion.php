<?php

use App\AdministracionSistema\Controllers\GestionController;
use App\Admision\Controllers\AdmisionController;
use App\GestionDocentes\Controllers\DocentesController;
use App\GestionEstudiantes\Controllers\EstudiantesController;
use App\OrganizacionAcademica\Controllers\GruposController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('administracion')->group(function () {
    Route::resource('gestiones', GestionController::class)
        ->except(['show'])
        ->parameters(['gestiones' => 'gestion']);
    Route::patch('gestiones/{gestion}/avanzar', [GestionController::class, 'avanzar'])->name('gestiones.avanzar');
    Route::patch('gestiones/{gestion}/retroceder', [GestionController::class, 'retroceder'])->name('gestiones.retroceder');

    Route::middleware('role:administrador')->prefix('docentes')->name('docentes.')->group(function () {
        Route::get('/', [DocentesController::class, 'index'])->name('index');
        Route::get('{user}/edit', [DocentesController::class, 'edit'])->name('edit');
        Route::patch('{user}', [DocentesController::class, 'update'])->name('update');
        Route::get('documento/descargar', [DocentesController::class, 'descargarDocumento'])->name('descargar-documento');
    });

    Route::middleware('role:administrador,coordinador')->prefix('estudiantes')->name('estudiantes.')->group(function () {
        Route::get('/', [EstudiantesController::class, 'index'])->name('index');
        Route::get('{estudiante}/edit', [EstudiantesController::class, 'edit'])->name('edit');
        Route::patch('{estudiante}', [EstudiantesController::class, 'update'])->name('update');
    });

    Route::middleware('role:administrador,coordinador')->prefix('grupos')->name('grupos.')->group(function () {
        Route::get('/', [GruposController::class, 'seleccionar'])->name('seleccionar');
        Route::get('{gestion}', [GruposController::class, 'index'])->name('index');
        Route::post('{gestion}/generar', [GruposController::class, 'generar'])->name('generar');
        Route::get('{gestion}/{nombre}/configurar', [GruposController::class, 'configurar'])->name('configurar');
        Route::patch('{gestion}/{nombre}', [GruposController::class, 'actualizar'])->name('actualizar');
    });

    Route::middleware('role:administrador,coordinador')->prefix('admision')->name('admision.')->group(function () {
        Route::get('/', [AdmisionController::class, 'index'])->name('index');

        Route::get('candidato-estudiante/{candidato}', [AdmisionController::class, 'revisarCandidatoEstudiante'])->name('candidato-estudiante.revisar');
        Route::patch('candidato-estudiante/{candidato}/aprobar', [AdmisionController::class, 'aprobarCandidatoEstudiante'])->name('candidato-estudiante.aprobar');
        Route::patch('candidato-estudiante/{candidato}/rechazar', [AdmisionController::class, 'rechazarCandidatoEstudiante'])->name('candidato-estudiante.rechazar');
        Route::patch('candidato-estudiante/{candidato}/solicitar-correcciones', [AdmisionController::class, 'solicitarCorreccionesEstudiante'])->name('candidato-estudiante.solicitar-correcciones');
        Route::delete('candidato-estudiante/{candidato}', [AdmisionController::class, 'eliminarCandidatoEstudiante'])->name('candidato-estudiante.eliminar');

        Route::get('candidato-docente/{candidato}', [AdmisionController::class, 'revisarCandidatoDocente'])->name('candidato-docente.revisar');
        Route::patch('candidato-docente/{candidato}/aprobar', [AdmisionController::class, 'aprobarCandidatoDocente'])->name('candidato-docente.aprobar');
        Route::patch('candidato-docente/{candidato}/rechazar', [AdmisionController::class, 'rechazarCandidatoDocente'])->name('candidato-docente.rechazar');
        Route::patch('candidato-docente/{candidato}/solicitar-correcciones', [AdmisionController::class, 'solicitarCorreccionesDocente'])->name('candidato-docente.solicitar-correcciones');
        Route::delete('candidato-docente/{candidato}', [AdmisionController::class, 'eliminarCandidatoDocente'])->name('candidato-docente.eliminar');

        Route::patch('requisitos/aprobar', [AdmisionController::class, 'aprobarRequisito'])->name('requisitos.aprobar');
        Route::patch('requisitos/rechazar', [AdmisionController::class, 'rechazarRequisito'])->name('requisitos.rechazar');
        Route::get('requisitos/descargar', [AdmisionController::class, 'descargarRequisito'])->name('requisitos.descargar');
    });
});
