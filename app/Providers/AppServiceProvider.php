<?php

namespace App\Providers;

use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\RegistroInscripcion\Models\CandidatoEstudiante;
use App\SeguridadAcceso\Models\Bitacora;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Relation::enforceMorphMap([
            'candidato_estudiante' => CandidatoEstudiante::class,
            'candidato_docente' => CandidatoDocente::class,
        ]);

        $this->registrarBitacoraDeSesiones();
    }

    /** Registra los inicios y cierres de sesión en la bitácora. */
    protected function registrarBitacoraDeSesiones(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            Bitacora::registrar('login', [
                'usuario_id' => $event->user->getAuthIdentifier(),
                'modulo' => 'Sesión',
                'descripcion' => 'Inició sesión',
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user) {
                return;
            }

            Bitacora::registrar('logout', [
                'usuario_id' => $event->user->getAuthIdentifier(),
                'modulo' => 'Sesión',
                'descripcion' => 'Cerró sesión',
            ]);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        // Política de contraseñas: el brief exige mayúscula + minúscula + número
        // como mínimo. En producción se endurece con símbolos y verificación.
        Password::defaults(fn (): Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : Password::min(8)
                ->mixedCase()
                ->numbers(),
        );
    }
}
