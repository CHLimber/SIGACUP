<?php

use App\AdministracionSistema\Models\Carrera;
use App\AdministracionSistema\Models\Gestion;
use App\AdministracionSistema\Models\Materia;
use App\OrganizacionAcademica\Models\Grupo;
use App\RegistroInscripcion\Controllers\PortalCandidatoController;
use App\RegistroInscripcion\Controllers\PortalPagoController;
use App\RegistroInscripcion\Controllers\RegistroController;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\RegistroInscripcion\Models\Pago;
use App\RegistroInscripcion\Models\Postulacion;
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
        'nota_minima' => (int) ($g?->parametro('nota_minima_aprobacion') ?? 60),
        'peso1' => (int) ($g?->parametro('peso_examen_1') ?? 30),
        'peso2' => (int) ($g?->parametro('peso_examen_2') ?? 30),
        'peso3' => (int) ($g?->parametro('peso_examen_3') ?? 40),
        'gestion_label' => $g ? "{$g->anio} · ".($g->semestre === 1 ? '1er Semestre' : '2do Semestre') : null,
        'gestion_estado' => $g?->estado,
    ];
}

Route::get('/', function () {
    $gestion = gestionActiva();

    return Inertia::render('Welcome', propsGestion($gestion));
})->name('home');

Route::post('/registro/estudiante', [RegistroController::class, 'storeCandidatoEstudiante'])->name('registro.estudiante');
Route::post('/registro/docente', [RegistroController::class, 'storeCandidatoDocente'])->name('registro.docente');

Route::prefix('candidato/{token}')->name('portal.candidato.')->group(function () {
    Route::get('requisitos', [PortalCandidatoController::class, 'show'])->name('show');
    Route::post('requisitos/{codigo}', [PortalCandidatoController::class, 'subir'])->name('subir');
    Route::delete('requisitos/{codigo}', [PortalCandidatoController::class, 'eliminar'])->name('eliminar');
    Route::get('requisitos/{codigo}/descargar', [PortalCandidatoController::class, 'descargar'])->name('descargar');
    Route::post('datos-profesionales', [PortalCandidatoController::class, 'guardarDatosProfesionales'])->name('datos-profesionales');
    Route::post('datos-academicos', [PortalCandidatoController::class, 'guardarDatosAcademicos'])->name('datos-academicos');
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
        $gid = $gestion?->id;

        $pagosCompletados = $gid
            ? Pago::where('estado', Pago::ESTADO_COMPLETADO)
                ->whereHas('postulacion', fn ($q) => $q->where('gestion_id', $gid))
            : null;

        $metricas = [
            'postulaciones' => $gid ? Postulacion::where('gestion_id', $gid)->count() : 0,
            'admitidos' => $gid ? Postulacion::where('gestion_id', $gid)->where('estado_admision', Postulacion::ADMISION_ADMITIDO)->count() : 0,
            'por_revisar' => CandidatoEstudiante::whereIn('estado', [
                CandidatoEstudiante::ESTADO_PENDIENTE,
                CandidatoEstudiante::ESTADO_EN_REVISION,
            ])->count(),
            'pagos' => $pagosCompletados ? (clone $pagosCompletados)->count() : 0,
            'monto_recaudado' => $pagosCompletados ? (float) (clone $pagosCompletados)->sum('monto_bs') : 0.0,
            'grupos' => $gid ? Grupo::where('gestion_id', $gid)->count() : 0,
            'carreras' => Carrera::count(),
            'materias' => Materia::count(),
            'carreras_nombres' => Carrera::orderBy('nombre')->pluck('nombre')->all(),
        ];

        return Inertia::render('Dashboard', array_merge(
            propsGestion($gestion),
            ['metricas' => $metricas],
        ));
    })->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/administracion.php';
