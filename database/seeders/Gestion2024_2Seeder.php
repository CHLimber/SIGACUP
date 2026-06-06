<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siembra la gestión 2024-2 completa:
 *  - ~1000 candidatos a estudiante con todos los estados del flujo
 *  - 16 docentes (4 por materia), con persona, user, candidato_docente y docente
 *  - Grupos (7 por materia × 4 materias = 28), aulas, asignaciones
 *  - Evaluaciones y pagos para admitidos y no-admitidos
 *    (no-admitidos repartidos en: reprobados, reprob. por una materia y sin cupo)
 *
 * Ejecución: php artisan db:seed --class=Gestion2024_2Seeder
 */
class Gestion2024_2Seeder extends Seeder
{
    // ── Pools de datos ───────────────────────────────────────────────────────

    private const APELLIDOS = [
        'Quispe', 'Mamani', 'García', 'Condori', 'López', 'Flores', 'Choque', 'Gutierrez',
        'Rojas', 'Vargas', 'Mendoza', 'Cruz', 'Torrico', 'Salazar', 'Morales', 'Ramos',
        'Fernández', 'Vásquez', 'Pérez', 'Romero', 'Álvarez', 'Torres', 'Jiménez', 'Ruiz',
        'Ortiz', 'Soria', 'Miranda', 'Herrera', 'Medina', 'Aguilar', 'Castellón', 'Limachi',
        'Catunta', 'Huanca', 'Apaza', 'Callisaya', 'Tola', 'Zenteno', 'Vega', 'Castillo',
        'Illanes', 'Quiroga', 'Ponce', 'Arancibia', 'Bernal', 'Carvajal', 'Delgado',
        'Espinoza', 'Fuentes', 'Gonzales', 'Chávez', 'Ríos', 'Tapia', 'Cortez', 'Blanco',
        'Campos', 'Silva', 'Núñez', 'Reyes', 'Cabrera', 'Molina', 'Lara', 'Pacheco',
        'Menacho', 'Mercado', 'Rivero', 'Suárez', 'Urquieta', 'Balcázar', 'Antezana',
    ];

    private const NOMBRES_M = [
        'Carlos', 'Luis', 'Juan', 'Pedro', 'Miguel', 'Fernando', 'Diego', 'Jorge', 'Roberto',
        'Rodrigo', 'Andrés', 'Alejandro', 'Christian', 'David', 'Eduardo', 'Fabio', 'Gabriel',
        'Héctor', 'Iván', 'José', 'Kevin', 'Leonardo', 'Marco', 'Nelson', 'Oscar', 'Pablo',
        'Raúl', 'Santiago', 'Tomás', 'Víctor', 'Walter', 'Alex', 'Boris', 'Dante', 'Ernesto',
        'Franco', 'Gustavo', 'Hugo', 'Ignacio', 'Javier', 'Kelvin', 'Manuel', 'Nicolás',
        'Rolando', 'Sergio', 'Ulises', 'Xavier', 'Yhon', 'Zenón',
    ];

    private const NOMBRES_F = [
        'María', 'Ana', 'Laura', 'Carmen', 'Rosa', 'Lucía', 'Patricia', 'Sandra', 'Claudia',
        'Daniela', 'Elena', 'Fátima', 'Gloria', 'Helena', 'Irene', 'Josefina', 'Karen',
        'Liliana', 'Mónica', 'Natalia', 'Olga', 'Paula', 'Rebeca', 'Silvana', 'Teresa',
        'Valeria', 'Wendy', 'Ximena', 'Yolanda', 'Adriana', 'Beatriz', 'Cecilia', 'Diana',
        'Erika', 'Fernanda', 'Gabriela', 'Ingrid', 'Jessica', 'Karina', 'Lorena', 'Marisol',
        'Noelia', 'Paola', 'Rosario', 'Stefanía', 'Verónica',
    ];

    private const UNIDADES = [
        'U.E. Simón Bolívar', 'U.E. 6 de Agosto', 'U.E. San Calixto', 'U.E. Germán Busch',
        'U.E. Bolivia', 'U.E. Don Bosco', 'U.E. Sagrado Corazón', 'U.E. 24 de Septiembre',
        'U.E. René Moreno', 'U.E. La Salle', 'U.E. Cristo Rey', 'U.E. Bethlemitas',
        'U.E. San Francisco', 'U.E. Santa Ana', 'U.E. Inmaculada Concepción', 'U.E. San Ignacio',
        'U.E. Mariscal Andrés de Santa Cruz', 'U.E. Nuestra Señora de Loreto',
        'U.E. Nacional Potosí', 'U.E. Franco Boliviano', 'U.E. Alemán',
        'U.E. Juan XXIII', 'U.E. Santa Cruz de la Sierra', 'U.E. Japón',
    ];

    private const CALLES = [
        'Av. Monseñor Rivero', 'Calle Independencia', 'Av. Alemana', 'Calle 24 de Septiembre',
        'Av. San Martín', 'Calle Junín', 'Av. Roca y Coronado', 'Calle Bolívar',
        'Av. Busch', 'Calle Sucre', 'Av. Bánzer', 'Calle Warnes', 'Av. Cristo Redentor',
        'Calle Cochabamba', 'Av. Uruguay', 'Calle Libertad',
    ];

    private const TIPOS_COLEGIO = ['publica', 'privada', 'convenio'];

    // ── Estado interno del seeder ────────────────────────────────────────────

    private int $ciCounter = 8_000_001;

    private int $telCounter = 71_000_001;

    private int $factura = 100_001;

    private int $estUser = 1;

    private string $nowStr = '';

    private array $materiasCache = [];

    // ── Entry point ──────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->nowStr = now()->toDateTimeString();
        $this->materiasCache = DB::table('materia')->orderBy('codigo')->pluck('codigo')->all();

        DB::transaction(function (): void {
            $gestionId = $this->crearGestion();
            $this->crearParametros($gestionId);
            $carreraIds = DB::table('carrera')->orderBy('id')->pluck('id')->all();
            $this->crearCupos($gestionId, $carreraIds);
            $aulaIds = $this->crearAulas();
            $horarioIds = DB::table('horario')->orderBy('id')->pluck('id')->all();
            $materias = DB::table('materia')->orderBy('codigo')->pluck('codigo')->all();
            $grupoIds = $this->crearGrupos($gestionId, $materias, $aulaIds, $horarioIds);
            $docenteIds = $this->crearDocentes();
            $this->asignarDocentesGrupos($materias, $grupoIds, $docenteIds);
            $this->crearEstudiantes($gestionId, $carreraIds, $materias, $grupoIds);
        });
    }

    // ── Gestión ──────────────────────────────────────────────────────────────

    private function crearGestion(): int
    {
        return DB::table('gestion')->insertGetId([
            'anio' => 2024,
            'semestre' => 2,
            'estado' => 'cerrada',
            'fecha_inicio_inscripcion' => '2024-07-15',
            'fecha_fin_inscripcion' => '2024-08-09',
            'fecha_inicio_cursado' => '2024-08-12',
            'fecha_fin_cursado' => '2024-11-29',
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);
    }

    private function crearParametros(int $gestionId): void
    {
        $params = [
            'monto_matricula_bs' => '800',
            'capacidad_max_grupo' => '70',
            'peso_examen_1' => '30',
            'peso_examen_2' => '30',
            'peso_examen_3' => '40',
            'nota_minima_aprobacion' => '60',
            'max_grupos_docente' => '5',
        ];
        foreach ($params as $clave => $valor) {
            DB::table('parametro')->insert([
                'gestion_id' => $gestionId,
                'clave' => $clave,
                'valor' => $valor,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }
    }

    // ── Cupos ────────────────────────────────────────────────────────────────

    private function crearCupos(int $gestionId, array $carreraIds): void
    {
        foreach ($carreraIds as $cid) {
            DB::table('cupo_carrera')->insert([
                'carrera_id' => $cid,
                'gestion_id' => $gestionId,
                'cupo_max' => 120,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }
    }

    // ── Aulas ────────────────────────────────────────────────────────────────

    private function crearAulas(): array
    {
        $aulas = [
            ['A-101', 80, 'Módulo A'], ['A-102', 80, 'Módulo A'],
            ['A-103', 80, 'Módulo A'], ['A-104', 80, 'Módulo A'],
            ['B-201', 80, 'Módulo B'], ['B-202', 80, 'Módulo B'],
            ['B-203', 80, 'Módulo B'], ['C-301', 75, 'Módulo C'],
            ['Lab-1', 45, 'Laboratorio'], ['Lab-2', 45, 'Laboratorio'],
        ];
        $ids = [];
        foreach ($aulas as [$nombre, $cap, $mod]) {
            $ids[] = DB::table('aula')->insertGetId([
                'nombre' => $nombre,
                'capacidad' => $cap,
                'modulo' => $mod,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }

        return $ids;
    }

    // ── Grupos ───────────────────────────────────────────────────────────────

    /** @return array<string, list<int>> materia => [grupo_id, ...] */
    private function crearGrupos(int $gestionId, array $materias, array $aulaIds, array $horarioIds): array
    {
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $grupos = [];
        $aIdx = 0;
        $hIdx = 0;

        foreach ($materias as $codigo) {
            $grupos[$codigo] = [];
            foreach ($letras as $letra) {
                $grupos[$codigo][] = DB::table('grupo')->insertGetId([
                    'gestion_id' => $gestionId,
                    'codigo_materia' => $codigo,
                    'nombre' => $letra,
                    'horario_id' => $horarioIds[$hIdx % count($horarioIds)],
                    'aula_id' => $aulaIds[$aIdx % count($aulaIds)],
                    'capacidad_max' => 70,
                    'created_at' => $this->nowStr,
                    'updated_at' => $this->nowStr,
                ]);
                $aIdx++;
                $hIdx++;
            }
        }

        return $grupos;
    }

    // ── Docentes ─────────────────────────────────────────────────────────────

    /**
     * Crea 16 docentes (4 por materia). Devuelve array keyed by código de materia.
     *
     * @return array<string, list<int>> materia => [docente_id, ...]
     */
    private function crearDocentes(): array
    {
        // [nombres, apellido, sexo, CI-base, titulo, exp, diplomado, maestria, materia]
        $docentesDef = [
            // LIN001
            ['Roberto',  'Paredes García',    'M', 7_000_001, 'Ingeniero de Sistemas',                  12, false, true,  'LIN001'],
            ['Ana',      'Montoya Vásquez',   'F', 7_000_002, 'Licenciada en Lingüística',               8, true,  false, 'LIN001'],
            ['Felipe',   'Saavedra Rojas',    'M', 7_000_003, 'Licenciado en Idiomas',                   5, false, false, 'LIN001'],
            ['Carmen',   'Ríos Blanco',       'F', 7_000_004, 'Magíster en Lingüística Aplicada',       15, true,  true,  'LIN001'],
            // MAT001
            ['Hugo',     'Fernández Cruz',    'M', 7_000_005, 'Ingeniero Matemático',                   10, false, true,  'MAT001'],
            ['Lorena',   'Vega Salinas',      'F', 7_000_006, 'Licenciada en Matemáticas',               7, true,  false, 'MAT001'],
            ['Jorge',    'Castellón Ponce',   'M', 7_000_007, 'Magíster en Matemáticas',                18, true,  true,  'MAT001'],
            ['Gustavo',  'Morales Soria',     'M', 7_000_008, 'Ingeniero Industrial',                    6, false, false, 'MAT001'],
            // INF001
            ['Carlos',   'Quispe Mamani',     'M', 7_000_009, 'Ingeniero de Sistemas',                   9, false, true,  'INF001'],
            ['Sandra',   'Condori Apaza',     'F', 7_000_010, 'Ingeniera Informática',                  11, true,  false, 'INF001'],
            ['Andrés',   'Mamani Tola',       'M', 7_000_011, 'Licenciado en Informática',               4, false, false, 'INF001'],
            ['Daniela',  'López Zenteno',     'F', 7_000_012, 'Magíster en Ciencias de la Computación', 14, true,  true,  'INF001'],
            // FIS001
            ['Pablo',    'Vargas Choque',     'M', 7_000_013, 'Ingeniero Físico',                        8, false, false, 'FIS001'],
            ['Silvana',  'Torrico Illanes',   'F', 7_000_014, 'Licenciada en Física',                   10, true,  false, 'FIS001'],
            ['Marco',    'Salazar Quiroga',   'M', 7_000_015, 'Magíster en Física Aplicada',            16, true,  true,  'FIS001'],
            ['Eduardo',  'Herrera Cabrera',   'M', 7_000_016, 'Ingeniero en Electrónica',                7, false, false, 'FIS001'],
        ];

        $hashPwd = Hash::make('Docente2024!');
        $resultado = [];

        foreach ($docentesDef as $n => [$nombres, $apellido, $sexo, $ci, $titulo, $exp, $diplomado, $maestria, $materia]) {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => (string) $ci,
                'apellido' => $apellido,
                'nombres' => $nombres,
                'fecha_nacimiento' => $this->fecha(1965, 1992),
                'sexo' => $sexo,
                'telefono' => '7'.str_pad((string) (2_000_001 + $n), 7, '0', STR_PAD_LEFT),
                'email' => 'docente.persona'.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT).'@gmail.com',
                'direccion' => 'Av. Universitaria #'.(200 + $n * 5),
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            $tokenDoc = bin2hex(random_bytes(32));

            $candDocenteId = DB::table('candidato_docente')->insertGetId([
                'persona_id' => $personaId,
                'estado' => 'aprobado',
                'token_acceso' => $tokenDoc,
                'motivo_rechazo' => null,
                'user_id' => null,
                'titulo' => $titulo,
                'experiencia_anios' => $exp,
                'tiene_diplomado' => $diplomado,
                'tiene_maestria' => $maestria,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            $this->insertarRequisitosDocente($candDocenteId, $diplomado, $maestria);

            $num = str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT);
            $email = "doc{$num}@ficct.edu.bo";

            $userId = DB::table('users')->insertGetId([
                'persona_id' => $personaId,
                'name' => "{$nombres} {$apellido}",
                'username' => "doc_{$num}",
                'email' => $email,
                'email_verified_at' => $this->nowStr,
                'password' => $hashPwd,
                'role' => 'docente',
                'activo' => true,
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            DB::table('candidato_docente')
                ->where('id', $candDocenteId)
                ->update(['user_id' => $userId]);

            $docenteId = DB::table('docente')->insertGetId([
                'user_id' => $userId,
                'titulo' => $titulo,
                'experiencia_anios' => $exp,
                'tiene_diplomado' => $diplomado,
                'tiene_maestria' => $maestria,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            $resultado[$materia][] = $docenteId;
        }

        return $resultado;
    }

    // ── Asignación docentes → grupos ─────────────────────────────────────────

    private function asignarDocentesGrupos(array $materias, array $grupoIds, array $docenteIds): void
    {
        // 7 grupos por materia, 4 docentes por materia → distribución 2-2-2-1
        $distribucion = [2, 2, 2, 1];

        foreach ($materias as $mat) {
            $grupos = $grupoIds[$mat] ?? [];
            $docentes = $docenteIds[$mat] ?? [];
            $gIdx = 0;

            foreach ($docentes as $dIdx => $docenteId) {
                $cantidad = $distribucion[$dIdx] ?? 1;
                for ($k = 0; $k < $cantidad && $gIdx < count($grupos); $k++, $gIdx++) {
                    DB::table('docente_grupo')->insert([
                        'docente_id' => $docenteId,
                        'grupo_id' => $grupos[$gIdx],
                        'created_at' => $this->nowStr,
                        'updated_at' => $this->nowStr,
                    ]);
                }
            }
        }
    }

    // ── Estudiantes ──────────────────────────────────────────────────────────

    private function crearEstudiantes(int $gestionId, array $carreraIds, array $materias, array $grupoIds): void
    {
        // Puntero round-robin por materia para distribuir grupos
        $rrGrupo = array_fill_keys($materias, 0);

        $nextGrupo = function (string $mat) use (&$rrGrupo, $grupoIds): int {
            $idx = $rrGrupo[$mat];
            $rrGrupo[$mat] = ($idx + 1) % count($grupoIds[$mat]);

            return $grupoIds[$mat][$idx];
        };

        $hashPwd = Hash::make('Estudiante2024!');

        // ── 480 ADMITIDOS (120 por carrera) ──────────────────────────
        for ($i = 0; $i < 480; $i++) {
            // Promedio entre 62 y 98 (bien repartido, decreciente para simular ranking)
            $promedio = round(98 - ($i / 479) * 36 + mt_rand(-3, 3), 2);
            $promedio = max(62.0, min(100.0, $promedio));

            // Carrera: 120 por cada una en orden
            $carrera1Idx = (int) ($i / 120);
            $carrera1Id = $carreraIds[$carrera1Idx];
            $carrera2Id = $carreraIds[($carrera1Idx + 1) % 4];

            $postId = $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'pagado',
                estadoPago: 'pagado',
                estadoCup: 'completado',
                estadoAdm: 'admitido',
                promedio: $promedio,
                carrera1Id: $carrera1Id,
                carrera2Id: $carrera2Id,
                carreraAsig: $carrera1Id,
                conUser: true,
                conPago: true,
                conReqs: 'aprobado',
                conEval: true,
                notaMin: 65,
                notaMax: 100,
            );

            // Asignación a grupos (1 por materia)
            foreach ($materias as $mat) {
                DB::table('asignacion_grupo')->insert([
                    'postulacion_id' => $postId,
                    'grupo_id' => $nextGrupo($mat),
                    'created_at' => $this->nowStr,
                    'updated_at' => $this->nowStr,
                ]);
            }
        }

        // ── 150 NO ADMITIDOS (pagaron, rindieron examen, no entraron) ─
        // Tres motivos distintos, para ilustrar las reglas de admisión:
        //    60 reprobados      → todas las materias por debajo de la mínima.
        //    45 reprob. materia → buen promedio, pero UNA materia < 60.
        //    45 sin cupo        → todas las materias ≥ 60, pero cupos llenos.
        for ($i = 0; $i < 150; $i++) {
            $unaMateriaReprobada = false;

            if ($i < 60) {
                $promedio = round(20 + ($i / 59) * 38 + mt_rand(-3, 3), 2);
                $promedio = max(10.0, min(58.0, $promedio));
                $notaMin = 10;
                $notaMax = 58;
            } elseif ($i < 105) {
                $promedio = round(66 + mt_rand(-3, 7), 2);
                $promedio = max(60.0, min(78.0, $promedio));
                $notaMin = 65;
                $notaMax = 90;
                $unaMateriaReprobada = true;
            } else {
                $j = $i - 105;
                $promedio = round(60 + ($j / 44) * 16 + mt_rand(-1, 1), 2);
                $promedio = max(60.0, min(78.0, $promedio));
                $notaMin = 60;
                $notaMax = 76;
            }

            $c1Idx = $i % 4;
            $carrera1Id = $carreraIds[$c1Idx];
            $carrera2Id = $carreraIds[($c1Idx + 2) % 4];

            $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'pagado',
                estadoPago: 'pagado',
                estadoCup: 'completado',
                estadoAdm: 'no_admitido',
                promedio: $promedio,
                carrera1Id: $carrera1Id,
                carrera2Id: $carrera2Id,
                carreraAsig: null,
                conUser: false,
                conPago: true,
                conReqs: 'aprobado',
                conEval: true,
                notaMin: $notaMin,
                notaMax: $notaMax,
                unaMateriaReprobada: $unaMateriaReprobada,
            );
        }

        // ── 50 APROBADO PENDIENTE DE PAGO ────────────────────────────
        for ($i = 0; $i < 50; $i++) {
            $c1Idx = $i % 4;
            $carrera1Id = $carreraIds[$c1Idx];
            $carrera2Id = $carreraIds[($c1Idx + 3) % 4];

            $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'aprobado_pendiente_pago',
                estadoPago: 'pendiente',
                estadoCup: 'pendiente',
                estadoAdm: 'pendiente',
                promedio: null,
                carrera1Id: $carrera1Id,
                carrera2Id: $carrera2Id,
                carreraAsig: null,
                conUser: false,
                conPago: false,
                conReqs: 'aprobado',
                conEval: false,
                notaMin: 0,
                notaMax: 0,
            );
        }

        // ── 150 EN REVISIÓN ───────────────────────────────────────────
        for ($i = 0; $i < 150; $i++) {
            $c1Idx = $i % 4;
            $carrera1Id = $carreraIds[$c1Idx];
            $carrera2Id = $carreraIds[($c1Idx + 1) % 4];

            $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'en_revision',
                estadoPago: 'pendiente',
                estadoCup: 'pendiente',
                estadoAdm: 'pendiente',
                promedio: null,
                carrera1Id: $carrera1Id,
                carrera2Id: $carrera2Id,
                carreraAsig: null,
                conUser: false,
                conPago: false,
                conReqs: 'pendiente_revision',
                conEval: false,
                notaMin: 0,
                notaMax: 0,
            );
        }

        // ── 100 REQUIERE CORRECCIONES ─────────────────────────────────
        for ($i = 0; $i < 100; $i++) {
            $c1Idx = $i % 4;
            $carrera1Id = $carreraIds[$c1Idx];
            $carrera2Id = $carreraIds[($c1Idx + 2) % 4];

            $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'requiere_correcciones',
                estadoPago: 'pendiente',
                estadoCup: 'pendiente',
                estadoAdm: 'pendiente',
                promedio: null,
                carrera1Id: $carrera1Id,
                carrera2Id: $carrera2Id,
                carreraAsig: null,
                conUser: false,
                conPago: false,
                conReqs: 'pendiente_revision',
                conEval: false,
                notaMin: 0,
                notaMax: 0,
            );
        }

        // ── 70 RECHAZADOS ─────────────────────────────────────────────
        $motivosRechazo = [
            'Documentación incompleta o ilegible.',
            'Título de bachiller no legible.',
            'La cédula de identidad presentada no corresponde al postulante.',
            'Certificado de nacimiento vencido.',
            'No cumple con los requisitos mínimos del reglamento.',
            'Datos personales no coinciden con documentación.',
        ];
        for ($i = 0; $i < 70; $i++) {
            $motivo = $motivosRechazo[$i % count($motivosRechazo)];

            $this->crearEstudianteCompleto(
                gestionId: $gestionId,
                hashPwd: $hashPwd,
                estado: 'rechazado',
                estadoPago: 'pendiente',
                estadoCup: 'pendiente',
                estadoAdm: 'pendiente',
                promedio: null,
                carrera1Id: $carreraIds[$i % 4],
                carrera2Id: $carreraIds[($i + 1) % 4],
                carreraAsig: null,
                conUser: false,
                conPago: false,
                conReqs: 'rechazado',
                conEval: false,
                notaMin: 0,
                notaMax: 0,
                motivoRechazo: $motivo,
            );
        }
    }

    // ── Helpers de creación de entidades ────────────────────────────────────

    /**
     * Crea persona → candidato_estudiante → [postulacion → pago → evaluaciones]
     * Devuelve el postulacion_id (o 0 si no se crea postulacion).
     */
    private function crearEstudianteCompleto(
        int $gestionId,
        string $hashPwd,
        string $estado,
        string $estadoPago,
        string $estadoCup,
        string $estadoAdm,
        ?float $promedio,
        int $carrera1Id,
        int $carrera2Id,
        ?int $carreraAsig,
        bool $conUser,
        bool $conPago,
        string $conReqs,
        bool $conEval,
        int $notaMin,
        int $notaMax,
        ?string $motivoRechazo = null,
        bool $unaMateriaReprobada = false,
    ): int {
        $seq = $this->ciCounter;
        $this->ciCounter++;

        $sexo = ($seq % 3 === 0) ? 'F' : 'M';
        $ap = self::APELLIDOS[$seq % count(self::APELLIDOS)];
        $ap2 = self::APELLIDOS[($seq * 7 + 13) % count(self::APELLIDOS)];

        $nombres = $sexo === 'M'
            ? self::NOMBRES_M[$seq % count(self::NOMBRES_M)]
            : self::NOMBRES_F[$seq % count(self::NOMBRES_F)];

        $apellido = "{$ap} {$ap2}";
        $ci = (string) $seq;
        $email = "cand{$seq}@gmail.com";
        $tel = (string) $this->telCounter++;
        $calle = self::CALLES[$seq % count(self::CALLES)];
        $num = ($seq % 900) + 100;

        $personaId = DB::table('persona')->insertGetId([
            'ci' => $ci,
            'apellido' => $apellido,
            'nombres' => $nombres,
            'fecha_nacimiento' => $this->fecha(1998, 2007),
            'sexo' => $sexo,
            'telefono' => $tel,
            'email' => $email,
            'direccion' => "{$calle} #{$num}",
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);

        $token = bin2hex(random_bytes(32));
        $candId = DB::table('candidato_estudiante')->insertGetId([
            'persona_id' => $personaId,
            'estado' => $estado,
            'token_acceso' => $token,
            'motivo_rechazo' => $motivoRechazo,
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);

        $this->insertarRequisitosEstudiante($candId, $conReqs);

        // Solo se crea postulacion cuando tiene datos académicos completos
        if (in_array($estado, ['pagado', 'aprobado_pendiente_pago', 'en_revision', 'requiere_correcciones'], true)) {
            $unidad = self::UNIDADES[$seq % count(self::UNIDADES)];
            $tipoCole = self::TIPOS_COLEGIO[$seq % count(self::TIPOS_COLEGIO)];
            $anioEgreso = 2018 + ($seq % 7);

            $postId = DB::table('postulacion')->insertGetId([
                'candidato_estudiante_id' => $candId,
                'gestion_id' => $gestionId,
                'carrera1_id' => $carrera1Id,
                'carrera2_id' => $carrera2Id,
                'anio_egreso' => $anioEgreso,
                'unidad_educativa' => $unidad,
                'tipo_colegio' => $tipoCole,
                'estado_pago' => $estadoPago,
                'estado_cup' => $estadoCup,
                'promedio_general' => $promedio,
                'carrera_asignada_id' => $carreraAsig,
                'estado_admision' => $estadoAdm,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            if ($conPago) {
                $this->insertarPago($postId);
            }

            if ($conEval) {
                $this->insertarEvaluaciones($postId, $this->materiasCache, $notaMin, $notaMax, $unaMateriaReprobada);
            }

            return $postId;
        }

        return 0;
    }

    // ── Requisitos ───────────────────────────────────────────────────────────

    private function insertarRequisitosEstudiante(int $candId, string $estadoReq): void
    {
        $codigos = ['fotocopia_ci', 'certificado_nacimiento', 'titulo_bachiller',
            'certificado_colegio', 'libreta_ultimo_anio', 'foto_carnet'];

        $revisadoAt = in_array($estadoReq, ['aprobado', 'rechazado'])
            ? $this->nowStr
            : null;

        foreach ($codigos as $codigo) {
            $ext = ($codigo === 'foto_carnet') ? 'jpg' : 'pdf';
            DB::table('requisito_estudiante')->insert([
                'candidato_estudiante_id' => $candId,
                'codigo' => $codigo,
                'nombre_original' => "{$codigo}.{$ext}",
                'ruta_archivo' => "requisitos/estudiantes/{$candId}/{$codigo}.{$ext}",
                'mime_type' => ($ext === 'pdf') ? 'application/pdf' : 'image/jpeg',
                'tamano' => mt_rand(80_000, 800_000),
                'estado' => $estadoReq,
                'motivo_rechazo' => ($estadoReq === 'rechazado') ? 'Documento ilegible.' : null,
                'revisado_at' => $revisadoAt,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }
    }

    private function insertarRequisitosDocente(int $candDocenteId, bool $diplomado, bool $maestria): void
    {
        $codigos = ['fotocopia_ci', 'cv', 'titulo_profesional', 'diploma_academico'];
        if ($maestria) {
            $codigos[] = 'certificado_maestria';
        }
        if ($diplomado) {
            $codigos[] = 'certificado_diplomado';
        }

        foreach ($codigos as $codigo) {
            DB::table('requisito_docente')->insert([
                'candidato_docente_id' => $candDocenteId,
                'codigo' => $codigo,
                'nombre_original' => "{$codigo}.pdf",
                'ruta_archivo' => "requisitos/docentes/{$candDocenteId}/{$codigo}.pdf",
                'mime_type' => 'application/pdf',
                'tamano' => mt_rand(50_000, 600_000),
                'estado' => 'aprobado',
                'motivo_rechazo' => null,
                'revisado_at' => $this->nowStr,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }
    }

    // ── Pagos ────────────────────────────────────────────────────────────────

    private function insertarPago(int $postulacionId): void
    {
        $num = str_pad((string) $this->factura++, 6, '0', STR_PAD_LEFT);
        $dia = str_pad((string) mt_rand(15, 30), 2, '0', STR_PAD_LEFT);
        $h = str_pad((string) mt_rand(8, 20), 2, '0', STR_PAD_LEFT);
        $m = str_pad((string) mt_rand(0, 59), 2, '0', STR_PAD_LEFT);

        DB::table('pago')->insert([
            'postulacion_id' => $postulacionId,
            'token_pago' => bin2hex(random_bytes(16)),
            'monto_bs' => 800.00,
            'monto_usd' => 114.94,
            'tasa_cambio' => 6.9600,
            'metodo' => 'stripe',
            'stripe_session_id' => 'cs_'.Str::random(24),
            'stripe_payment_intent_id' => 'pi_'.Str::random(24),
            'referencia_externa' => null,
            'numero_factura' => "FAC-2024-{$num}",
            'estado' => 'completado',
            'fecha' => "2024-07-{$dia} {$h}:{$m}:00",
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);
    }

    // ── Evaluaciones ─────────────────────────────────────────────────────────

    /**
     * Crea 3 exámenes × 4 materias = 12 evaluaciones por postulación. Si
     * $unaMateriaReprobada es true, una materia al azar recibe notas por debajo
     * de la mínima (35–55) mientras el resto usa la banda [$min, $max]; ilustra a
     * quien reprueba la admisión por una sola materia pese a tener buen promedio.
     */
    private function insertarEvaluaciones(int $postId, array $materias, int $min, int $max, bool $unaMateriaReprobada = false): void
    {
        $pesos = [1 => 30, 2 => 30, 3 => 40];
        $rows = [];

        $idxReprobada = $unaMateriaReprobada ? array_rand($materias) : -1;

        foreach ($materias as $idx => $codigo) {
            [$mn, $mx] = ($idx === $idxReprobada) ? [35, 55] : [$min, $max];

            foreach ([1, 2, 3] as $examen) {
                $nota = mt_rand($mn, $mx) + mt_rand(0, 99) / 100;
                $nota = min((float) $mx, max((float) $mn, round($nota, 2)));

                $rows[] = [
                    'postulacion_id' => $postId,
                    'codigo_materia' => $codigo,
                    'numero_examen' => $examen,
                    'nota_cruda' => $nota,
                    'peso' => $pesos[$examen],
                    'created_at' => $this->nowStr,
                    'updated_at' => $this->nowStr,
                ];
            }
        }

        foreach (array_chunk($rows, 100) as $chunk) {
            DB::table('evaluacion')->insert($chunk);
        }
    }

    // ── Utilidades ───────────────────────────────────────────────────────────

    private function fecha(int $desde, int $hasta): string
    {
        $y = mt_rand($desde, $hasta);
        $m = str_pad((string) mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
        $d = str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT);

        return "{$y}-{$m}-{$d}";
    }
}
