<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 1. Elimina la gestión 2026-1 y todos sus datos.
 * 2. Limpia todo lo sembrado para 2025-2 (candidatos, docentes, grupos, etc.).
 * 3. Re-siembra 2025-2 en estado "inscripcion":
 *      - Docentes y grupos creados como siempre.
 *      - ~1 100 candidatos en distintas etapas del flujo de inscripción.
 *      - Nadie tiene evaluaciones CUP ni asignación a grupos.
 *
 * Ejecución:  php artisan db:seed --class=Gestion2025_2InscripcionSeeder
 */
class Gestion2025_2InscripcionSeeder extends Seeder
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
    ];

    private const CALLES = [
        'Av. Monseñor Rivero', 'Calle Independencia', 'Av. Alemana', 'Calle 24 de Septiembre',
        'Av. San Martín', 'Calle Junín', 'Av. Roca y Coronado', 'Calle Bolívar',
        'Av. Busch', 'Calle Sucre', 'Av. Bánzer', 'Calle Warnes', 'Av. Cristo Redentor',
        'Calle Cochabamba', 'Av. Uruguay', 'Calle Libertad', 'Av. Tres Pasos al Frente',
    ];

    private const TIPOS_COLEGIO = ['publica', 'privada', 'convenio'];

    // ── Estado interno ───────────────────────────────────────────────────────

    private int $ciCounter = 10_000_001;   // mismo rango que el seeder anterior (ya fue borrado)

    private int $telCounter = 78_000_001;

    private int $factura = 300_001;

    private string $nowStr = '';

    private array $materiasCache = [];

    // ── Entry point ──────────────────────────────────────────────────────────

    public function run(): void
    {
        $this->nowStr = now()->toDateTimeString();
        $this->materiasCache = DB::table('materia')->orderBy('codigo')->pluck('codigo')->all();

        DB::transaction(function (): void {
            $this->limpiar();

            $gestionId = $this->obtenerGestion();
            $carreraIds = DB::table('carrera')->orderBy('id')->pluck('id')->all();
            $aulaIds = $this->obtenerOCrearAulas();
            $horarioIds = DB::table('horario')->orderBy('id')->pluck('id')->all();
            $grupoIds = $this->crearGrupos($gestionId, $horarioIds, $aulaIds);
            $docenteIds = $this->crearDocentes();
            $this->asignarDocentesGrupos($grupoIds, $docenteIds);
            $this->crearCandidatos($gestionId, $carreraIds);
        });
    }

    // ── Limpieza ─────────────────────────────────────────────────────────────

    private function limpiar(): void
    {
        // ── Borrar gestión 2026-1 ──────────────────────────────────────────
        $g2026 = DB::table('gestion')->where('anio', 2026)->where('semestre', 1)->value('id');
        if ($g2026) {
            DB::table('cupo_carrera')->where('gestion_id', $g2026)->delete();
            DB::table('parametro')->where('gestion_id', $g2026)->delete();
            DB::table('gestion')->where('id', $g2026)->delete();
        }

        // ── Limpiar datos sembrados en 2025-2 ─────────────────────────────
        $g2025_2 = DB::table('gestion')->where('anio', 2025)->where('semestre', 2)->value('id');
        if (! $g2025_2) {
            return;
        }

        // Postulaciones y todo lo que cuelga de ellas
        $postIds = DB::table('postulacion')
            ->where('gestion_id', $g2025_2)
            ->pluck('id');

        $candIds = DB::table('postulacion')
            ->where('gestion_id', $g2025_2)
            ->pluck('candidato_estudiante_id')
            ->unique();

        DB::table('evaluacion')->whereIn('postulacion_id', $postIds)->delete();
        DB::table('asignacion_grupo')->whereIn('postulacion_id', $postIds)->delete();
        DB::table('pago')->whereIn('postulacion_id', $postIds)->delete();
        DB::table('postulacion')->where('gestion_id', $g2025_2)->delete();

        // Candidatos estudiante (con requisitos, users, personas)
        $candidatos = DB::table('candidato_estudiante')->whereIn('id', $candIds)->get(['id', 'user_id', 'persona_id']);
        $estUserIds = $candidatos->pluck('user_id')->filter()->values()->all();
        $estPersonaIds = $candidatos->pluck('persona_id')->all();

        DB::table('requisito_estudiante')->whereIn('candidato_estudiante_id', $candIds)->delete();
        DB::table('candidato_estudiante')->whereIn('id', $candIds)->delete();
        if ($estUserIds) {
            DB::table('users')->whereIn('id', $estUserIds)->delete();
        }
        DB::table('persona')->whereIn('id', $estPersonaIds)->delete();

        // Docentes de 2025-2 (via docente_grupo → grupos de esta gestión)
        $grupoIds2025_2 = DB::table('grupo')->where('gestion_id', $g2025_2)->pluck('id');
        $docenteIds = DB::table('docente_grupo')->whereIn('grupo_id', $grupoIds2025_2)->pluck('docente_id')->unique();
        $docUserIds = DB::table('docente')->whereIn('id', $docenteIds)->pluck('user_id');

        DB::table('docente_grupo')->whereIn('grupo_id', $grupoIds2025_2)->delete();
        DB::table('docente')->whereIn('id', $docenteIds)->delete();

        $candDoc = DB::table('candidato_docente')->whereIn('user_id', $docUserIds)->get(['id', 'persona_id']);
        $candDocIds = $candDoc->pluck('id')->all();
        $docPersonaIds = $candDoc->pluck('persona_id')->all();

        DB::table('requisito_docente')->whereIn('candidato_docente_id', $candDocIds)->delete();
        DB::table('candidato_docente')->whereIn('id', $candDocIds)->delete();
        DB::table('users')->whereIn('id', $docUserIds)->delete();
        DB::table('persona')->whereIn('id', $docPersonaIds)->delete();

        // Grupos
        DB::table('grupo')->where('gestion_id', $g2025_2)->delete();
    }

    // ── Gestión ──────────────────────────────────────────────────────────────

    private function obtenerGestion(): int
    {
        $id = DB::table('gestion')->where('anio', 2025)->where('semestre', 2)->value('id');

        if ($id) {
            // Actualizar a estado 'inscripcion' para reflejar que estamos en ese punto
            DB::table('gestion')->where('id', $id)->update([
                'estado' => 'inscripcion',
                'updated_at' => $this->nowStr,
            ]);

            return $id;
        }

        // Fallback: crear si no existe
        $id = DB::table('gestion')->insertGetId([
            'anio' => 2025,
            'semestre' => 2,
            'estado' => 'inscripcion',
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

        foreach (DB::table('carrera')->pluck('id') as $cid) {
            DB::table('cupo_carrera')->insertOrIgnore([
                'carrera_id' => $cid, 'gestion_id' => $id, 'cupo_max' => 120,
                'created_at' => $this->nowStr, 'updated_at' => $this->nowStr,
            ]);
        }

        return $id;
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
            $ex = DB::table('aula')->where('nombre', $nombre)->value('id');
            $ids[] = $ex ?? DB::table('aula')->insertGetId([
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
        $aIdx = $hIdx = 0;

        foreach ($this->materiasCache as $codigo) {
            $grupos[$codigo] = [];
            foreach ($letras as $letra) {
                $grupos[$codigo][] = DB::table('grupo')->insertGetId([
                    'gestion_id' => $gestionId,
                    'codigo_materia' => $codigo,
                    'nombre' => $letra,
                    'horario_id' => $horarioIds[$hIdx++ % count($horarioIds)],
                    'aula_id' => $aulaIds[$aIdx++ % count($aulaIds)],
                    'capacidad_max' => 70,
                    'created_at' => $this->nowStr,
                    'updated_at' => $this->nowStr,
                ]);
            }
        }

        return $grupos;
    }

    // ── Docentes ─────────────────────────────────────────────────────────────

    /** @return array<string, list<int>> */
    private function crearDocentes(): array
    {
        $defs = [
            ['Juana',     'Soruco Ferrufino',  'F', 7_600_001, 'Licenciada en Lingüística',                9, false, true,  'LIN001'],
            ['Cristóbal', 'Landivar Becerra',  'M', 7_600_002, 'Magíster en Lingüística y Literatura',   18, true,  true,  'LIN001'],
            ['Milagros',  'Arispe Justiniano', 'F', 7_600_003, 'Licenciada en Idiomas',                    6, true,  false, 'LIN001'],
            ['Freddy',    'Villarroel Ortiz',  'M', 7_600_004, 'Licenciado en Letras',                     4, false, false, 'LIN001'],
            ['Nataly',    'Cuellar Terán',     'F', 7_600_005, 'Magíster en Matemáticas Aplicadas',       14, true,  true,  'MAT001'],
            ['Rubén',     'Nogales Vásquez',   'M', 7_600_006, 'Ingeniero en Sistemas',                    7, false, false, 'MAT001'],
            ['Susana',    'Zeballos Campos',   'F', 7_600_007, 'Licenciada en Matemáticas',                8, true,  false, 'MAT001'],
            ['Ernesto',   'Paniagua Ríos',     'M', 7_600_008, 'Magíster en Investigación Operativa',     16, true,  true,  'MAT001'],
            ['Gonzalo',   'Guzmán Castillo',   'M', 7_600_009, 'Magíster en Ciencias de la Computación', 13, true,  true,  'INF001'],
            ['Vanessa',   'Ibáñez Molina',     'F', 7_600_010, 'Ingeniera Informática',                    9, false, false, 'INF001'],
            ['Roly',      'Menacho Peñaranda', 'M', 7_600_011, 'Ingeniero de Sistemas',                    5, true,  false, 'INF001'],
            ['Nathaly',   'Balcázar Cabrera',  'F', 7_600_012, 'Licenciada en Informática',                6, false, false, 'INF001'],
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

            $candDocId = DB::table('candidato_docente')->insertGetId([
                'persona_id' => $personaId,
                'estado' => 'aprobado',
                'token_acceso' => bin2hex(random_bytes(32)),
                'motivo_rechazo' => null,
                'user_id' => null,
                'titulo' => $titulo,
                'experiencia_anios' => $exp,
                'tiene_diplomado' => $diplomado,
                'tiene_maestria' => $maestria,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            $this->insertarRequisitosDocente($candDocId, $diplomado, $maestria);

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

            DB::table('candidato_docente')->where('id', $candDocId)->update(['user_id' => $userId]);

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

    // ── Candidatos ───────────────────────────────────────────────────────────

    /**
     * Distribución — 1 100 candidatos, todos en flujo de inscripción (sin CUP):
     *
     *   pendiente               80  — solo se registraron, sin documentos ni postulación
     *   en_revision            300  — documentos enviados, en revisión del admin
     *   requiere_correcciones  140  — admin devolvió con observaciones
     *   aprobado_pendiente_pago 200 — admin aprobó, esperan pagar
     *   pagado                 320  — pagaron la matrícula, esperan el CUP
     *   rechazado               60  — solicitud rechazada por admin
     */
    private function crearCandidatos(int $gestionId, array $carreraIds): void
    {
        $motivosRechazo = [
            'Documentación incompleta o ilegible.',
            'Título de bachiller no legible.',
            'La cédula de identidad no corresponde al postulante.',
            'Certificado de nacimiento vencido o ilegible.',
            'No cumple con los requisitos mínimos del reglamento.',
            'Datos personales no coinciden con la documentación.',
            'Fotografía no cumple con los requisitos (fondo rojo, 4×4).',
            'Libreta del último año con tachaduras no certificadas.',
        ];

        // pendiente — sin documentos, sin postulación
        for ($i = 0; $i < 80; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'pendiente',
                conReqs: false, conPostulacion: false, conPago: false);
        }

        // en_revision — todos los docs en pendiente_revision, con postulación
        for ($i = 0; $i < 300; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'en_revision',
                conReqs: true, estadoReqs: 'pendiente_revision',
                conPostulacion: true, conPago: false);
        }

        // requiere_correcciones — mezcla: 4 docs aprobados, 2 rechazados
        for ($i = 0; $i < 140; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'requiere_correcciones',
                conReqs: true, estadoReqs: 'mixto',
                conPostulacion: true, conPago: false);
        }

        // aprobado_pendiente_pago — todos los docs aprobados, sin pago
        for ($i = 0; $i < 200; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'aprobado_pendiente_pago',
                conReqs: true, estadoReqs: 'aprobado',
                conPostulacion: true, conPago: false);
        }

        // pagado — todos los docs aprobados, con pago, CUP pendiente
        for ($i = 0; $i < 320; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'pagado',
                conReqs: true, estadoReqs: 'aprobado',
                conPostulacion: true, conPago: true);
        }

        // rechazado — docs rechazados, sin postulación
        for ($i = 0; $i < 60; $i++) {
            $this->crearCandidato($gestionId, $carreraIds, 'rechazado',
                conReqs: true, estadoReqs: 'rechazado',
                conPostulacion: false, conPago: false,
                motivoRechazo: $motivosRechazo[$i % count($motivosRechazo)]);
        }
    }

    private function crearCandidato(
        int $gestionId,
        array $carreraIds,
        string $estado,
        bool $conReqs,
        string $estadoReqs = 'aprobado',
        bool $conPostulacion = false,
        bool $conPago = false,
        ?string $motivoRechazo = null,
    ): void {
        $seq = $this->ciCounter++;
        $sexo = ($seq % 3 === 0) ? 'F' : 'M';

        $ap = self::APELLIDOS[$seq % count(self::APELLIDOS)];
        $ap2 = self::APELLIDOS[($seq * 11 + 7) % count(self::APELLIDOS)];
        $nombres = $sexo === 'M'
            ? self::NOMBRES_M[$seq % count(self::NOMBRES_M)]
            : self::NOMBRES_F[$seq % count(self::NOMBRES_F)];
        $calle = self::CALLES[$seq % count(self::CALLES)];

        $personaId = DB::table('persona')->insertGetId([
            'ci' => (string) $seq,
            'apellido' => "{$ap} {$ap2}",
            'nombres' => $nombres,
            'fecha_nacimiento' => $this->fecha(1998, 2008),
            'sexo' => $sexo,
            'telefono' => (string) $this->telCounter++,
            'email' => "cand252_{$seq}@gmail.com",
            'direccion' => "{$calle} #{(($seq % 900) + 100)}",
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);

        $candId = DB::table('candidato_estudiante')->insertGetId([
            'persona_id' => $personaId,
            'estado' => $estado,
            'token_acceso' => bin2hex(random_bytes(32)),
            'motivo_rechazo' => $motivoRechazo,
            'user_id' => null,   // sin cuenta hasta ser admitido
            'created_at' => $this->nowStr,
            'updated_at' => $this->nowStr,
        ]);

        if ($conReqs) {
            $this->insertarRequisitosEstudiante($candId, $estadoReqs);
        }

        if ($conPostulacion) {
            $c1Idx = $seq % 4;
            $postId = DB::table('postulacion')->insertGetId([
                'candidato_estudiante_id' => $candId,
                'gestion_id' => $gestionId,
                'carrera1_id' => $carreraIds[$c1Idx],
                'carrera2_id' => $carreraIds[($c1Idx + 1) % 4],
                'anio_egreso' => 2019 + ($seq % 6),
                'unidad_educativa' => self::UNIDADES[$seq % count(self::UNIDADES)],
                'tipo_colegio' => self::TIPOS_COLEGIO[$seq % count(self::TIPOS_COLEGIO)],
                'estado_pago' => $conPago ? 'pagado' : 'pendiente',
                'estado_cup' => 'pendiente',
                'promedio_general' => null,
                'carrera_asignada_id' => null,
                'estado_admision' => 'pendiente',
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);

            if ($conPago) {
                $num = str_pad((string) $this->factura++, 6, '0', STR_PAD_LEFT);
                $dia = str_pad((string) mt_rand(14, 31), 2, '0', STR_PAD_LEFT);
                $h = str_pad((string) mt_rand(8, 20), 2, '0', STR_PAD_LEFT);
                $m = str_pad((string) mt_rand(0, 59), 2, '0', STR_PAD_LEFT);

                DB::table('pago')->insert([
                    'postulacion_id' => $postId,
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
        }
    }

    // ── Requisitos ───────────────────────────────────────────────────────────

    private function insertarRequisitosEstudiante(int $candId, string $modo): void
    {
        $codigos = ['fotocopia_ci', 'certificado_nacimiento', 'titulo_bachiller',
            'certificado_colegio', 'libreta_ultimo_anio', 'foto_carnet'];

        foreach ($codigos as $idx => $codigo) {
            if ($modo === 'mixto') {
                // Primeros 4 aprobados, últimos 2 rechazados
                $estadoReq = $idx < 4 ? 'aprobado' : 'rechazado';
                $revisadoAt = $this->nowStr;
                $motivo = $estadoReq === 'rechazado' ? 'Documento ilegible o incompleto.' : null;
            } else {
                $estadoReq = $modo;
                $revisadoAt = in_array($modo, ['aprobado', 'rechazado']) ? $this->nowStr : null;
                $motivo = $modo === 'rechazado' ? 'Documento ilegible.' : null;
            }

            $ext = ($codigo === 'foto_carnet') ? 'jpg' : 'pdf';
            DB::table('requisito_estudiante')->insert([
                'candidato_estudiante_id' => $candId,
                'codigo' => $codigo,
                'nombre_original' => "{$codigo}.{$ext}",
                'ruta_archivo' => "requisitos/estudiantes/{$candId}/{$codigo}.{$ext}",
                'mime_type' => ($ext === 'pdf') ? 'application/pdf' : 'image/jpeg',
                'tamano' => mt_rand(80_000, 800_000),
                'estado' => $estadoReq,
                'motivo_rechazo' => $motivo,
                'revisado_at' => $revisadoAt,
                'created_at' => $this->nowStr,
                'updated_at' => $this->nowStr,
            ]);
        }
    }

    private function insertarRequisitosDocente(int $candDocId, bool $diplomado, bool $maestria): void
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
                'candidato_docente_id' => $candDocId,
                'codigo' => $codigo,
                'nombre_original' => "{$codigo}.pdf",
                'ruta_archivo' => "requisitos/docentes/{$candDocId}/{$codigo}.pdf",
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

    // ── Utilidades ───────────────────────────────────────────────────────────

    private function fecha(int $desde, int $hasta): string
    {
        $y = mt_rand($desde, $hasta);
        $m = str_pad((string) mt_rand(1, 12), 2, '0', STR_PAD_LEFT);
        $d = str_pad((string) mt_rand(1, 28), 2, '0', STR_PAD_LEFT);

        return "{$y}-{$m}-{$d}";
    }
}
