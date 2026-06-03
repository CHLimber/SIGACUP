<?php

use App\AdministracionSistema\Controllers\GestionController;
use App\Admision\Controllers\AdmisionController;
use App\Admision\Controllers\ProcesoAdmisionController;
use App\Calificaciones\Controllers\CalificacionesController;
use App\GestionDocentes\Controllers\DocentesController;
use App\GestionEstudiantes\Controllers\EstudiantesController;
use App\OrganizacionAcademica\Controllers\GruposController;
use App\Reportes\Controllers\ReporteController;
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
        Route::get('{gestion}/asignar-docentes', [GruposController::class, 'docentes'])->name('docentes');
        Route::patch('{gestion}/asignar-docentes', [GruposController::class, 'asignarDocentes'])->name('docentes.guardar');
        Route::post('{gestion}/asignar-docentes/auto', [GruposController::class, 'autoAsignarDocentes'])->name('docentes.auto');
        Route::get('{gestion}/{nombre}/configurar', [GruposController::class, 'configurar'])->name('configurar');
        Route::patch('{gestion}/{nombre}', [GruposController::class, 'actualizar'])->name('actualizar');
    });

    Route::prefix('calificaciones')->name('calificaciones.')->group(function () {
        Route::get('/', [CalificacionesController::class, 'index'])->name('index');
        Route::get('{grupo}', [CalificacionesController::class, 'calificar'])->name('calificar');
        Route::put('{grupo}', [CalificacionesController::class, 'guardar'])->name('guardar');
    });

    Route::middleware('role:administrador,coordinador')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('resumen', [ReporteController::class, 'resumen'])->name('resumen');
        Route::get('exportar/csv', [ReporteController::class, 'exportarCsv'])->name('exportar.csv');
    });

    Route::middleware('role:administrador,coordinador')->prefix('proceso-admision')->name('proceso-admision.')->group(function () {
        Route::get('/', [ProcesoAdmisionController::class, 'index'])->name('index');
        Route::get('{gestion}', [ProcesoAdmisionController::class, 'show'])->name('show');
        Route::post('{gestion}/ejecutar', [ProcesoAdmisionController::class, 'ejecutar'])->name('ejecutar');
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
