<?php

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\GestionEstudiantes\Models\Postulacion;
use App\InscripcionPagos\Controllers\PortalPagoController;
use App\RegistroPublico\Controllers\PortalCandidatoController;
use App\RegistroPublico\Controllers\RegistroController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

function gestionActiva(): ?Gestion
{
    return Gestion::where('estado', '!=', 'cerrada')
        ->orderByDesc('anio')
        ->orderByDesc('semestre')
        ->with('parametros')
        ->first();
}

function propsGestion(?Gestion $g): array
{
    return [
        'nota_minima'    => (int) ($g?->parametro('nota_minima_aprobacion') ?? 60),
        'peso1'          => (int) ($g?->parametro('peso_examen_1') ?? 30),
        'peso2'          => (int) ($g?->parametro('peso_examen_2') ?? 30),
        'peso3'          => (int) ($g?->parametro('peso_examen_3') ?? 40),
        'gestion_label'  => $g ? "{$g->anio} · " . ($g->semestre === 1 ? '1er Semestre' : '2do Semestre') : null,
        'gestion_estado' => $g?->estado,
    ];
}

Route::get('/', function () {
    $gestion = gestionActiva();

    return Inertia::render('Welcome', array_merge(
        propsGestion($gestion),
        ['carreras' => Carrera::orderBy('nombre')->get(['id', 'nombre'])],
    ));
})->name('home');

Route::post('/registro/estudiante', [RegistroController::class, 'storeCandidatoEstudiante'])->name('registro.estudiante');
Route::post('/registro/docente', [RegistroController::class, 'storeCandidatoDocente'])->name('registro.docente');

Route::prefix('candidato/{token}')->name('portal.candidato.')->group(function () {
    Route::get('requisitos', [PortalCandidatoController::class, 'show'])->name('show');
    Route::post('requisitos/{codigo}', [PortalCandidatoController::class, 'subir'])->name('subir');
    Route::delete('requisitos/{codigo}', [PortalCandidatoController::class, 'eliminar'])->name('eliminar');
    Route::get('requisitos/{codigo}/descargar', [PortalCandidatoController::class, 'descargar'])->name('descargar');
    Route::post('datos-profesionales', [PortalCandidatoController::class, 'guardarDatosProfesionales'])->name('datos-profesionales');
    Route::post('enviar', [PortalCandidatoController::class, 'enviar'])->name('enviar');
});

Route::prefix('matricula/{token}')->name('portal.matricula.')->group(function () {
    Route::get('/', [PortalPagoController::class, 'show'])->name('show');
    Route::post('payment-intent', [PortalPagoController::class, 'crearPaymentIntent'])->name('payment-intent');
    Route::post('confirmar', [PortalPagoController::class, 'confirmar'])->name('confirmar');
    Route::get('comprobante', [PortalPagoController::class, 'comprobante'])->name('comprobante');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $gestion = gestionActiva();

        $candidatos = $gestion
            ? Postulacion::where('gestion_id', $gestion->id)->count()
            : 0;

        return Inertia::render('Dashboard', array_merge(
            propsGestion($gestion),
            ['candidatos' => $candidatos],
        ));
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/administracion.php';
