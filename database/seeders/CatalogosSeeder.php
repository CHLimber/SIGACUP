<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('carrera')->insert([
            ['nombre' => 'Ingeniería de Sistemas',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ingeniería Informática',    'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ingeniería en Redes',       'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Ingeniería en Robótica',    'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('materia')->insert([
            ['codigo' => 'LIN001', 'nombre' => 'Inglés',        'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'MAT001', 'nombre' => 'Matemáticas',   'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'INF001', 'nombre' => 'Computación',   'created_at' => now(), 'updated_at' => now()],
            ['codigo' => 'FIS001', 'nombre' => 'Física',        'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('gestion')->insert([
            [
                'anio' => 2025, 'semestre' => 1, 'estado' => 'cerrada',
                'fecha_inicio_inscripcion' => '2025-02-10', 'fecha_fin_inscripcion' => '2025-02-28',
                'fecha_inicio_cursado'     => '2025-03-03', 'fecha_fin_cursado'     => '2025-03-07',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'anio' => 2025, 'semestre' => 2, 'estado' => 'cerrada',
                'fecha_inicio_inscripcion' => '2025-07-14', 'fecha_fin_inscripcion' => '2025-07-31',
                'fecha_inicio_cursado'     => '2025-08-04', 'fecha_fin_cursado'     => '2025-08-08',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'anio' => 2026, 'semestre' => 1, 'estado' => 'configuracion',
                'fecha_inicio_inscripcion' => '2026-02-09', 'fecha_fin_inscripcion' => '2026-02-27',
                'fecha_inicio_cursado'     => '2026-03-02', 'fecha_fin_cursado'     => '2026-03-06',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        DB::table('parametro_catalogo')->insert([
            ['clave' => 'monto_matricula_bs',     'tipo' => 'decimal', 'descripcion' => 'Precio de la matrícula en Bs',          'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'capacidad_max_grupo',    'tipo' => 'entero',  'descripcion' => 'Capacidad máxima de alumnos por grupo', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'peso_examen_1',          'tipo' => 'entero',  'descripcion' => 'Peso % del primer parcial',               'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'peso_examen_2',          'tipo' => 'entero',  'descripcion' => 'Peso % del segundo parcial',              'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'peso_examen_3',          'tipo' => 'entero',  'descripcion' => 'Peso % del examen final',                 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'nota_minima_aprobacion', 'tipo' => 'entero',  'descripcion' => 'Nota mínima por materia para aprobar',    'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'max_grupos_docente',     'tipo' => 'entero',  'descripcion' => 'Máximo de grupos por docente',            'created_at' => now(), 'updated_at' => now()],
        ]);

        $gestiones = DB::table('gestion')->pluck('id');
        $params = [
            ['clave' => 'monto_matricula_bs',     'valor' => '800'],
            ['clave' => 'capacidad_max_grupo',    'valor' => '70'],
            ['clave' => 'peso_examen_1',          'valor' => '30'],
            ['clave' => 'peso_examen_2',          'valor' => '30'],
            ['clave' => 'peso_examen_3',          'valor' => '40'],
            ['clave' => 'nota_minima_aprobacion', 'valor' => '60'],
            ['clave' => 'max_grupos_docente',     'valor' => '5'],
        ];

        foreach ($gestiones as $gestion_id) {
            foreach ($params as $p) {
                DB::table('parametro')->insert([
                    'gestion_id' => $gestion_id,
                    'clave'      => $p['clave'],
                    'valor'      => $p['valor'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $horarios = [
            ['07:00', '08:30'], ['08:30', '10:00'], ['10:00', '11:30'], ['11:30', '13:00'],
            ['13:00', '14:30'], ['14:30', '16:00'], ['16:00', '17:30'], ['17:30', '19:00'],
            ['19:00', '20:30'], ['20:30', '22:00'],
        ];

        foreach ($horarios as [$inicio, $fin]) {
            DB::table('horario')->insert([
                'aplica_todos_dias' => true,
                'dia'               => null,
                'hora_inicio'       => $inicio,
                'hora_fin'          => $fin,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        // Cupos por carrera y gestión (usados por el proceso de admisión).
        $carreras = DB::table('carrera')->pluck('id');

        foreach ($gestiones as $gestion_id) {
            foreach ($carreras as $carrera_id) {
                DB::table('cupo_carrera')->insert([
                    'carrera_id' => $carrera_id,
                    'gestion_id' => $gestion_id,
                    'cupo_max'   => 30,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
