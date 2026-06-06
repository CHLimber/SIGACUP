<?php

use App\AdministracionSistema\Controllers\GestionController;
use App\EvaluacionAdmision\Controllers\AdmisionController;
use App\EvaluacionAdmision\Controllers\CalificacionesController;
use App\EvaluacionAdmision\Controllers\ProcesoAdmisionController;
use App\OrganizacionAcademica\Controllers\DocentesController;
use App\OrganizacionAcademica\Controllers\GruposController;
use App\RegistroInscripcion\Controllers\EstudiantesController;
use App\ReportesNotificaciones\Controllers\ReporteController;
use App\ReportesNotificaciones\Controllers\ReporteIAController;
use App\SeguridadAcceso\Controllers\BitacoraController;
use App\SeguridadAcceso\Controllers\RolesController;
use App\SeguridadAcceso\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->prefix('administracion')->group(function () {
    Route::middleware('permiso:gestiones.gestionar')->group(function () {
        Route::resource('gestiones', GestionController::class)
            ->except(['show'])
            ->parameters(['gestiones' => 'gestion']);
        Route::patch('gestiones/{gestion}/avanzar', [GestionController::class, 'avanzar'])->name('gestiones.avanzar');
        Route::patch('gestiones/{gestion}/retroceder', [GestionController::class, 'retroceder'])->name('gestiones.retroceder');
    });

    Route::middleware('permiso:docentes.gestionar')->prefix('docentes')->name('docentes.')->group(function () {
        Route::get('/', [DocentesController::class, 'index'])->name('index');
        Route::get('{user}/edit', [DocentesController::class, 'edit'])->name('edit');
        Route::patch('{user}', [DocentesController::class, 'update'])->name('update');
        Route::get('documento/descargar', [DocentesController::class, 'descargarDocumento'])->name('descargar-documento');
    });

    Route::middleware('permiso:estudiantes.gestionar')->prefix('estudiantes')->name('estudiantes.')->group(function () {
        Route::get('/', [EstudiantesController::class, 'index'])->name('index');
        Route::get('{estudiante}/edit', [EstudiantesController::class, 'edit'])->name('edit');
        Route::patch('{estudiante}', [EstudiantesController::class, 'update'])->name('update');
    });

    Route::middleware('permiso:grupos.gestionar')->prefix('grupos')->name('grupos.')->group(function () {
        Route::get('/', [GruposController::class, 'seleccionar'])->name('seleccionar');
        Route::get('{gestion}', [GruposController::class, 'index'])->name('index');
        Route::post('{gestion}/generar', [GruposController::class, 'generar'])->name('generar');
        Route::delete('{gestion}/limpiar', [GruposController::class, 'limpiar'])->name('limpiar');
        Route::get('{gestion}/asignar-docentes', [GruposController::class, 'docentes'])->name('docentes');
        Route::patch('{gestion}/asignar-docentes', [GruposController::class, 'asignarDocentes'])->name('docentes.guardar');
        Route::post('{gestion}/asignar-docentes/auto', [GruposController::class, 'autoAsignarDocentes'])->name('docentes.auto');
        Route::get('{gestion}/{nombre}/configurar', [GruposController::class, 'configurar'])->name('configurar');
        Route::patch('{gestion}/{nombre}', [GruposController::class, 'actualizar'])->name('actualizar');
    });

    Route::middleware('permiso:calificaciones.gestionar')->prefix('calificaciones')->name('calificaciones.')->group(function () {
        Route::get('/', [CalificacionesController::class, 'index'])->name('index');
        Route::get('ponderadas', [CalificacionesController::class, 'ponderadas'])->name('ponderadas');
        Route::get('{grupo}', [CalificacionesController::class, 'calificar'])->name('calificar');
        Route::put('{grupo}', [CalificacionesController::class, 'guardar'])->name('guardar');
    });

    Route::middleware('permiso:reportes.ver')->prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('resumen', [ReporteController::class, 'resumen'])->name('resumen');
        Route::get('resumen/pdf', [ReporteController::class, 'resumenPdf'])->name('resumen.pdf');
        Route::get('exportar/csv', [ReporteController::class, 'exportarCsv'])->name('exportar.csv');
        Route::get('exportar/pdf', [ReporteController::class, 'exportarPdf'])->name('exportar.pdf');
        Route::get('ia', [ReporteIAController::class, 'index'])->name('ia.index');
        Route::post('ia/consultar', [ReporteIAController::class, 'consultar'])->name('ia.consultar');
    });

    Route::middleware('permiso:proceso_admision.gestionar')->prefix('proceso-admision')->name('proceso-admision.')->group(function () {
        Route::get('/', [ProcesoAdmisionController::class, 'index'])->name('index');
        Route::get('{gestion}', [ProcesoAdmisionController::class, 'show'])->name('show');
        Route::post('{gestion}/ejecutar', [ProcesoAdmisionController::class, 'ejecutar'])->name('ejecutar');
    });

    Route::middleware('permiso:admision.gestionar')->prefix('admision')->name('admision.')->group(function () {
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

    Route::middleware('permiso:usuarios.gestionar')->prefix('usuarios')->name('usuarios.')->group(function () {
        Route::get('/', [UsuariosController::class, 'index'])->name('index');
        Route::get('plantilla', [UsuariosController::class, 'plantillaCsv'])->name('plantilla');
        Route::post('importar/preview', [UsuariosController::class, 'previsualizarImport'])->name('importar.preview');
        Route::post('importar', [UsuariosController::class, 'importar'])->name('importar');
        Route::post('/', [UsuariosController::class, 'store'])->name('store');
        Route::patch('{user}', [UsuariosController::class, 'update'])->name('update');
        Route::patch('{user}/toggle', [UsuariosController::class, 'toggleActivo'])->name('toggle');
        Route::delete('{user}', [UsuariosController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('permiso:roles.gestionar')->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RolesController::class, 'index'])->name('index');
        Route::post('/', [RolesController::class, 'store'])->name('store');
        Route::patch('{rol}', [RolesController::class, 'update'])->name('update');
        Route::delete('{rol}', [RolesController::class, 'destroy'])->name('destroy');
    });

    Route::middleware('permiso:bitacora.ver')->prefix('bitacora')->name('bitacora.')->group(function () {
        Route::get('/', [BitacoraController::class, 'index'])->name('index');
    });
});
