<?php

namespace Database\Seeders;

use App\SeguridadAcceso\Models\Permiso;
use App\SeguridadAcceso\Models\Rol;
use Illuminate\Database\Seeder;

class RolesPermisosSeeder extends Seeder
{
    /** Catálogo de permisos: slug => [label, grupo]. */
    private const PERMISOS = [
        'gestiones.gestionar' => ['Gestionar gestiones académicas', 'Académico'],
        'grupos.gestionar' => ['Gestionar grupos', 'Académico'],
        'calificaciones.gestionar' => ['Gestionar calificaciones', 'Académico'],
        'admision.gestionar' => ['Revisar candidatos y admisión', 'Admisión'],
        'proceso_admision.gestionar' => ['Ejecutar proceso de admisión', 'Admisión'],
        'estudiantes.gestionar' => ['Gestionar estudiantes', 'Personas'],
        'docentes.gestionar' => ['Gestionar docentes', 'Personas'],
        'reportes.ver' => ['Ver reportes', 'Reportes'],
        'usuarios.gestionar' => ['Gestionar usuarios', 'Seguridad'],
        'roles.gestionar' => ['Gestionar roles y permisos', 'Seguridad'],
        'bitacora.ver' => ['Ver bitácora del sistema', 'Seguridad'],
    ];

    /** Roles del sistema: slug => [label, descripción, permisos[] | '*']. */
    private const ROLES = [
        'administrador' => ['Administrador', 'Acceso total al sistema.', '*'],
        'coordinador' => ['Coordinador', 'Gestión académica y de admisión.', [
            'gestiones.gestionar', 'grupos.gestionar', 'calificaciones.gestionar',
            'admision.gestionar', 'proceso_admision.gestionar',
            'estudiantes.gestionar', 'docentes.gestionar', 'reportes.ver',
        ]],
        'autoridad' => ['Autoridad', 'Consulta de reportes e indicadores.', ['reportes.ver']],
        'docente' => ['Docente', 'Acceso al panel docente.', []],
        'estudiante' => ['Estudiante', 'Acceso al portal del estudiante.', []],
    ];

    public function run(): void
    {
        foreach (self::PERMISOS as $nombre => [$label, $grupo]) {
            Permiso::updateOrCreate(['nombre' => $nombre], ['label' => $label, 'grupo' => $grupo]);
        }

        $permisos = Permiso::pluck('id', 'nombre');

        foreach (self::ROLES as $nombre => [$label, $descripcion, $perms]) {
            $rol = Rol::updateOrCreate(
                ['nombre' => $nombre],
                ['label' => $label, 'descripcion' => $descripcion, 'es_sistema' => true],
            );

            $ids = $perms === '*'
                ? $permisos->values()->all()
                : collect($perms)->map(fn ($p) => $permisos[$p] ?? null)->filter()->all();

            $rol->permisos()->sync($ids);
        }
    }
}
