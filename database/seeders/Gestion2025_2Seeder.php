<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Siembra la gestión 2025-2 completa (~1 150 candidatos).
 *
 * La gestión y sus parámetros base los crea CatalogosSeeder; este seeder
 * los busca por (anio=2025, semestre=2) y actualiza los cupos a 120 por
 * carrera, luego agrega docentes, grupos y estudiantes.
 *
 * Rangos reservados para no colisionar con seeders anteriores:
 *   Seeder 2024-2 → doc CI 7 000 001-016  | est CI 8 000 001+  | FAC-2024-*
 *   Seeder 2025-1 → doc CI 7 500 001-016  | est CI 9 000 001+  | FAC-2025-*
 *   Seeder 2025-2 → doc CI 7 600 001-016  | est CI 10 000 001+ | FAC-20252-*
 *
 * Ejecución:  php artisan db:seed --class=Gestion2025_2Seeder
 */
class Gestion2025_2Seeder extends Seeder
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
        'Terán', 'Cuellar', 'Nogales', 'Zeballos', 'Paniagua', 'Guzmán', 'Ibáñez',
        'Justiniano', 'Arispe', 'Soruco', 'Ferrufino', 'Landivar', 'Becerra', 'Villarroel',
    ];

    private const NOMBRES_M = [
        'Carlos', 'Luis', 'Juan', 'Pedro', 'Miguel', 'Fernando', 'Diego', 'Jorge', 'Roberto',
        'Rodrigo', 'Andrés', 'Alejandro', 'Christian', 'David', 'Eduardo', 'Fabio', 'Gabriel',
        'Héctor', 'Iván', 'José', 'Kevin', 'Leonardo', 'Marco', 'Nelson', 'Oscar', 'Pablo',
        'Raúl', 'Santiago', 'Tomás', 'Víctor', 'Walter', 'Alex', 'Boris', 'Dante', 'Ernesto',
        'Franco', 'Gustavo', 'Hugo', 'Ignacio', 'Javier', 'Kelvin', 'Manuel', 'Nicolás',
        'Rolando', 'Sergio', 'Ulises', 'Xavier', 'Yhon', 'Zenón', 'Adrián', 'Benjamín',
        'César', 'Donovan', 'Ezequiel', 'Fidel', 'Giovanni', 'Hilario',
    ];

    private const NOMBRES_F = [
        'María', 'Ana', 'Laura', 'Carmen', 'Rosa', 'Lucía', 'Patricia', 'Sandra', 'Claudia',
        'Daniela', 'Elena', 'Fátima', 'Gloria', 'Helena', 'Irene', 'Josefina', 'Karen',
        'Liliana', 'Mónica', 'Natalia', 'Olga', 'Paula', 'Rebeca', 'Silvana', 'Teresa',
        'Valeria', 'Wendy', 'Ximena', 'Yolanda', 'Adriana', 'Beatriz', 'Cecilia', 'Diana',
        'Erika', 'Fernanda', 'Gabriela', 'Ingrid', 'Jessica', 'Karina', 'Lorena', 'Marisol',
        'Noelia', 'Paola', 'Rosario', 'Stefanía', 'Verónica', 'Alejandra', 'Brenda',
        'Cinthia', 'Dayana', 'Estefany', 'Flavia', 'Griselda',
    ];

    private const UNIDADES = [
        'U.E. Simón Bolívar', 'U.E. 6 de Agosto', 'U.E. San Calixto', 'U.E. Germán Busch',
        'U.E. Bolivia', 'U.E. Don Bosco', 'U.E. Sagrado Corazón', 'U.E. 24 de Septiembre',
        'U.E. René Moreno', 'U.E. La Salle', 'U.E. Cristo Rey', 'U.E. Bethlemitas',
        'U.E. San Francisco', 'U.E. Santa Ana', 'U.E. Inmaculada Concepción', 'U.E. San Ignacio',
        'U.E. Mariscal Andrés de Santa Cruz', 'U.E. Nuestra Señora de Loreto',
        'U.E. Nacional Potosí', 'U.E. Franco Boliviano', 'U.E. Alemán',
        'U.E. Juan XXIII', 'U.E. Santa Cruz de la Sierra', 'U.E. Japón',
        'U.E. Nueva Esperanza', 'U.E. San José de Calasanz', 'U.E. República Argentina',
        'U.E. Ejercito de Salvación', 'U.E. Ángel Sandoval',
    ];

    private const CALLES = [
        'Av. Monseñor Rivero', 'Calle Independencia', 'Av. Alemana', 'Calle 24 de Septiembre',
        'Av. San Martín', 'Calle Junín', 'Av. Roca y Coronado', 'Calle Bolívar',
        'Av. Busch', 'Calle Sucre', 'Av. Bánzer', 'Calle Warnes', 'Av. Cristo Redentor',
        'Calle Cochabamba', 'Av. Uruguay', 'Calle Libertad', 'Av. Tres Pasos al Frente',
        'Calle Seoane', 'Av. Piraí', 'Calle Florida',
    ];

    private const TIPOS_COLEGIO = ['publica', 'privada', 'convenio'];

    // ── Estado interno ───────────────────────────────────────────────────────

    private int $ciCounter = 10_000_001;

    private int $telCounter = 78_000_001;

    private int $factura = 300_001;

    private int $estUser = 1;

    private string $nowStr = '';

    private array $materiasCache = [];

    // ── Entry point ──────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->nowStr = now()->toDateTimeString();
        $this->materiasCache = DB::table('materia')->orderBy('codigo')->pluck('codigo')->all();

        DB::transaction(function (): void {
            $gestionId = $this->obtenerGestion();
            $this->actualizarCupos($gestionId);
            $carreraIds = DB::table('carrera')->orderBy('id')->pluck('id')->all();
            $aulaIds = $this->obtenerOCrearAulas();
            $horarioIds = DB::table('horario')->orderBy('id')->pluck('id')->all();
            $grupoIds = $this->crearGrupos($gestionId, $horarioIds, $aulaIds);
            $docenteIds = $this->crearDocentes();
            $this->asignarDocentesGrupos($grupoIds, $docenteIds);
            $this->crearEstudiantes($gestionId, $carreraIds, $grupoIds);
        });
    }

    // ── Gestión ──────────────────────────────────────────────────────────────

    private function obtenerGestion(): int
    {
        $id = DB::table('gestion')
            ->where('anio', 2025)
            ->where('semestre', 2)
            ->value('id');

        if ($id) {
            return $id;
        }

        $id = DB::table('gestion')->insertGetId([
            'anio' => 2025,
            'semestre' => 2,
            'estado' => 'cerrada',
            'fecha_inicio_inscripcion' => '2025-07-14',
            'fecha_fin_inscripcion' => '2025-07-31',
            'fecha_inicio_cursado' => '2025-08-04',
            'fecha_fin_cursado' => '2025-11-28',
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);

        $params = [
            'monto_matricula_bs' => '800', 'capacidad_max_grupo' => '70',
            'peso_examen_1' => '30',       'peso_examen_2' => '30',
            'peso_examen_3' => '40',       'nota_minima_aprobacion' => '60',
            'max_grupos_docente' => '5',
        ];
        foreach ($params as $clave => $valor) {
            DB::table('parametro')->insertOrIgnore([
                'gestion_id' => $id, 'clave' => $clave, 'valor' => $valor,
                'created_at' => $this->nowStr, 'updated_at' => $this->nowStr,
            ]);
        }

        return $id;
    }

    // ── Cupos ────────────────────────────────────────────────────────────────

    private function actualizarCupos(int $gestionId): void
    {
        foreach (DB::table('carrera')->pluck('id') as $cid) {
            if (DB::table('cupo_carrera')->where('gestion_id', $gestionId)->where('carrera_id', $cid)->exists()) {
                DB::table('cupo_carrera')
                    ->where('gestion_id', $gestionId)->where('carrera_id', $cid)
                    ->update(['cupo_max' => 120, 'updated_at' => $this->nowStr]);
            } else {
                DB::table('cupo_carrera')->insert([
                    'carrera_id' => $cid, 'gestion_id' => $gestionId, 'cupo_max' => 120,
                    'created_at' => $this->nowStr, 'updated_at' => $this->nowStr,
                ]);
            }
        }
    }

    // ── Aulas ────────────────────────────────────────────────────────────────

    private function obtenerOCrearAulas(): array
    {
        $defs = [
            ['A-101', 80, 'Módulo A'], ['A-102', 80, 'Módulo A'],
            ['A-103', 80, 'Módulo A'], ['A-104', 80, 'Módulo A'],
            ['B-201', 80, 'Módulo B'], ['B-202', 80, 'Módulo B'],
            ['B-203', 80, 'Módulo B'], ['C-301', 75, 'Módulo C'],
            ['Lab-1', 45, 'Laboratorio'], ['Lab-2', 45, 'Laboratorio'],
        ];
        $ids = [];
        foreach ($defs as [$nombre, $cap, $mod]) {
            $existing = DB::table('aula')->where('nombre', $nombre)->value('id');
            $ids[] = $existing ?? DB::table('aula')->insertGetId([
                'nombre' => $nombre, 'capacidad' => $cap, 'modulo' => $mod,
                'created_at' => $this->nowStr, 'updated_at' => $this->nowStr,
            ]);
        }

        return $ids;
    }

    // ── Grupos ───────────────────────────────────────────────────────────────

    /** @return array<string, list<int>> */
    private function crearGrupos(int $gestionId, array $horarioIds, array $aulaIds): array
    {
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $grupos = [];
        $aIdx = 0;
        $hIdx = 0;

        foreach ($this->materiasCache as $codigo) {
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
     * 16 docentes nuevos para 2025-2 (CI 7 600 001–016, username doc252_01…16).
     *
     * @return array<string, list<int>>
     */
    private function crearDocentes(): array
    {
        $defs = [
            // LIN001
            ['Juana',     'Soruco Ferrufino',  'F', 7_600_001, 'Licenciada en Lingüística',                9, false, true,  'LIN001'],
            ['Cristóbal', 'Landivar Becerra',  'M', 7_600_002, 'Magíster en Lingüística y Literatura',   18, true,  true,  'LIN001'],
            ['Milagros',  'Arispe Justiniano', 'F', 7_600_003, 'Licenciada en Idiomas',                    6, true,  false, 'LIN001'],
            ['Freddy',    'Villarroel Ortiz',  'M', 7_600_004, 'Licenciado en Letras',                     4, false, false, 'LIN001'],
            // MAT001
            ['Nataly',    'Cuellar Terán',     'F', 7_600_005, 'Magíster en Matemáticas Aplicadas',       14, true,  true,  'MAT001'],
            ['Rubén',     'Nogales Vásquez',   'M', 7_600_006, 'Ingeniero en Sistemas',                    7, false, false, 'MAT001'],
            ['Susana',    'Zeballos Campos',   'F', 7_600_007, 'Licenciada en Matemáticas',                8, true,  false, 'MAT001'],
            ['Ernesto',   'Paniagua Ríos',     'M', 7_600_008, 'Magíster en Investigación Operativa',     16, true,  true,  'MAT001'],
            // INF001
            ['Gonzalo',   'Guzmán Castillo',   'M', 7_600_009, 'Magíster en Ciencias de la Computación', 13, true,  true,  'INF001'],
            ['Vanessa',   'Ibáñez Molina',     'F', 7_600_010, 'Ingeniera Informática',                    9, false, false, 'INF001'],
            ['Roly',      'Menacho Peñaranda', 'M', 7_600_011, 'Ingeniero de Sistemas',                    5, true,  false, 'INF001'],
            ['Nathaly',   'Balcázar Cabrera',  'F', 7_600_012, 'Licenciada en Informática',                6, false, false, 'INF001'],
            // FIS001
            ['Mauricio',  'Antezana Soria',    'M', 7_600_013, 'Magíster en Física Nuclear',              15, true,  true,  'FIS001'],
            ['Lissette',  'Urquieta Silva',    'F', 7_600_014, 'Licenciada en Física',                     8, false, false, 'FIS001'],
            ['Darwin',    'Rivero Lara',       'M', 7_600_015, 'Ingeniero en Electrónica',                 6, true,  false, 'FIS001'],
            ['Daniela',   'Mercado Pacheco',   'F', 7_600_016, 'Magíster en Astrofísica',                 11, true,  true,  'FIS001'],
        ];

        $hashPwd = Hash::make('Docente2025B!');
        $resultado = [];

        foreach ($defs as $n => [$nombres, $apellido, $sexo, $ci, $titulo, $exp, $diplomado, $maestria, $materia]) {
            $personaId = DB::table('persona')->insertGetId([
                'ci' => (string) $ci,
                'apellido' => $apellido,
                'nombres' => $nombres,
                'fecha_nacimiento' => $this->fecha(1965, 1992),
                'sexo' => $sexo,
                'telefono' => '7'.str_pad((string) (4_000_001 + $n), 7, '0', STR_PAD_LEFT),
                'email' => 'doc2025b.persona'.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT).'@gmail.com',
                'direccion' => 'Av. Universitaria Norte #'.(400 + $n * 5),
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
            $userId = DB::table('users')->insertGetId([
                'persona_id' => $personaId,
                'name' => "{$nombres} {$apellido}",
                'username' => "doc252_{$num}",
                'email' => "doc252_{$num}@ficct.edu.bo",
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

    private function asignarDocentesGrupos(array $grupoIds, array $docenteIds): void
    {
        $dist = [2, 2, 2, 1];

        foreach ($this->materiasCache as $mat) {
            $grupos = $grupoIds[$mat] ?? [];
            $docentes = $docenteIds[$mat] ?? [];
            $gIdx = 0;

            foreach ($docentes as $dIdx => $docenteId) {
                $cant = $dist[$dIdx] ?? 1;
                for ($k = 0; $k < $cant && $gIdx < count($grupos); $k++, $gIdx++) {
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

    /**
     * Distribución total: 1 150 candidatos — proceso completo hasta resultado final.
     *   480 admitidos    (120 × 4 carreras) — con notas, pago y asignación a grupos
     *   670 no_admitidos — pagaron y rindieron el CUP, repartidos en tres motivos:
     *       200 reprobados (todas las materias < mínima)
     *       135 reprobados por una sola materia (buen promedio, una materia < 60)
     *       335 sin cupo   (todas las materias ≥ 60, pero cupos llenos)
     */
    private function crearEstudiantes(int $gestionId, array $carreraIds, array $grupoIds): void
    {
        $hashPwd = Hash::make('Estudiante2025B!');
        $rrGrupo = array_fill_keys($this->materiasCache, 0);

        $nextGrupo = function (string $mat) use (&$rrGrupo, $grupoIds): int {
            $idx = $rrGrupo[$mat];
            $rrGrupo[$mat] = ($idx + 1) % count($grupoIds[$mat]);

            return $grupoIds[$mat][$idx];
        };

        // ── 480 ADMITIDOS (120 por carrera) ───────────────────────────
        for ($i = 0; $i < 480; $i++) {
            $promedio = round(98 - ($i / 479) * 36 + mt_rand(-3, 3), 2);
            $promedio = max(61.0, min(100.0, $promedio));
            $c1Idx = (int) ($i / 120);
            $carrera1Id = $carreraIds[$c1Idx];
            $carrera2Id = $carreraIds[($c1Idx + 1) % 4];

            $postId = $this->crearEstudianteCompleto(
                $gestionId, $hashPwd,
                'pagado', 'pagado', 'completado', 'admitido',
                $promedio, $carrera1Id, $carrera2Id, $carrera1Id,
                true, true, 'aprobado', true, 65, 100,
            );

            foreach ($this->materiasCache as $mat) {
                DB::table('asignacion_grupo')->insert([
                    'postulacion_id' => $postId,
                    'grupo_id' => $nextGrupo($mat),
                    'created_at' => $this->nowStr,
                    'updated_at' => $this->nowStr,
                ]);
            }
        }

        // ── 670 NO ADMITIDOS ──────────────────────────────────────────
        // Tres motivos distintos, para ilustrar las reglas de admisión:
        //   200 reprobados      → todas las materias por debajo de la mínima.
        //   135 reprob. materia → buen promedio, pero UNA materia < 60 (la regla
        //                          los reprueba aunque el promedio alcance).
        //   335 sin cupo        → todas las materias ≥ 60, pero los cupos ya
        //                          estaban llenos cuando llegó su turno.
        for ($i = 0; $i < 670; $i++) {
            $unaMateriaReprobada = false;

            if ($i < 200) {
                // Reprobados totales: notas 10–59 en todas las materias.
                $promedio = round(10 + ($i / 199) * 49 + mt_rand(-2, 2), 2);
                $promedio = max(10.0, min(59.0, $promedio));
                $notaMin = 10;
                $notaMax = 59;
            } elseif ($i < 335) {
                // Reprobados por una sola materia: 3 materias en 65–90 y una < 60.
                $promedio = round(66 + mt_rand(-3, 7), 2);
                $promedio = max(60.0, min(78.0, $promedio));
                $notaMin = 65;
                $notaMax = 90;
                $unaMateriaReprobada = true;
            } else {
                // Sin cupo: notas 60–76 (aprobaron, pero no había lugar).
                $j = $i - 335;
                $promedio = round(60 + ($j / 334) * 15 + mt_rand(-1, 1), 2);
                $promedio = max(60.0, min(76.0, $promedio));
                $notaMin = 60;
                $notaMax = 76;
            }

            $this->crearEstudianteCompleto(
                $gestionId, $hashPwd,
                'pagado', 'pagado', 'completado', 'no_admitido',
                $promedio, $carreraIds[$i % 4], $carreraIds[($i + 2) % 4], null,
                false, true, 'aprobado', true, $notaMin, $notaMax,
                unaMateriaReprobada: $unaMateriaReprobada,
            );
        }
    }

    // ── Helper: estudiante completo ──────────────────────────────────────────

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
        $seq = $this->ciCounter++;
        $sexo = ($seq % 3 === 0) ? 'F' : 'M';

        $ap = self::APELLIDOS[$seq % count(self::APELLIDOS)];
        $ap2 = self::APELLIDOS[($seq * 11 + 7) % count(self::APELLIDOS)];
        $nombres = $sexo === 'M'
            ? self::NOMBRES_M[$seq % count(self::NOMBRES_M)]
            : self::NOMBRES_F[$seq % count(self::NOMBRES_F)];
        $apellido = "{$ap} {$ap2}";
        $calle = self::CALLES[$seq % count(self::CALLES)];
        $num = ($seq % 900) + 100;

        $personaId = DB::table('persona')->insertGetId([
            'ci' => (string) $seq,
            'apellido' => $apellido,
            'nombres' => $nombres,
            'fecha_nacimiento' => $this->fecha(1998, 2008),
            'sexo' => $sexo,
            'telefono' => (string) $this->telCounter++,
            'email' => "cand252_{$seq}@gmail.com",
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

        if (in_array($estado, ['pagado', 'aprobado_pendiente_pago', 'en_revision', 'requiere_correcciones'], true)) {
            $postId = DB::table('postulacion')->insertGetId([
                'candidato_estudiante_id' => $candId,
                'gestion_id' => $gestionId,
                'carrera1_id' => $carrera1Id,
                'carrera2_id' => $carrera2Id,
                'anio_egreso' => 2019 + ($seq % 7),
                'unidad_educativa' => self::UNIDADES[$seq % count(self::UNIDADES)],
                'tipo_colegio' => self::TIPOS_COLEGIO[$seq % count(self::TIPOS_COLEGIO)],
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
                $this->insertarEvaluaciones($postId, $notaMin, $notaMax, $unaMateriaReprobada);
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
        $revisadoAt = in_array($estadoReq, ['aprobado', 'rechazado']) ? $this->nowStr : null;

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
        $dia = str_pad((string) mt_rand(14, 31), 2, '0', STR_PAD_LEFT);
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
            'numero_factura' => "FAC-20252-{$num}",
            'estado' => 'completado',
            'fecha' => "2025-07-{$dia} {$h}:{$m}:00",
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);
    }

    // ── Evaluaciones ─────────────────────────────────────────────────────────

    /**
     * Crea 3 exámenes por materia. Si $unaMateriaReprobada es true, una materia
     * al azar recibe notas por debajo de la mínima (35–55) mientras el resto usa
     * la banda [$min, $max]; sirve para ilustrar a quien reprueba la admisión por
     * una sola materia pese a tener buen promedio.
     */
    private function insertarEvaluaciones(int $postId, int $min, int $max, bool $unaMateriaReprobada = false): void
    {
        $pesos = [1 => 30, 2 => 30, 3 => 40];
        $rows = [];

        $idxReprobada = $unaMateriaReprobada ? array_rand($this->materiasCache) : -1;

        foreach ($this->materiasCache as $idx => $codigo) {
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
