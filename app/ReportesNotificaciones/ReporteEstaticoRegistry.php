<?php

namespace App\ReportesNotificaciones;

use Illuminate\Support\Facades\DB;

/**
 * Catálogo de reportes estáticos: cada reporte es una consulta SQL fija que se
 * ejecuta tal cual está escrita, sin filtros ni parámetros. Para agregar uno
 * nuevo basta con añadir su definición en $reportes.
 */
class ReporteEstaticoRegistry
{
    /**
     * @var array<string, array{label:string, descripcion:string, columnas:array<int, array{key:string, label:string, type:string}>, sql:string}>
     */
    private array $reportes;

    public function __construct()
    {
        $this->reportes = [
            'postulaciones_por_gestion' => [
                'label' => 'Panorama por gestión',
                'descripcion' => 'Total de postulaciones por gestión, desglosado por estado de admisión y de pago.',
                'columnas' => [
                    ['key' => 'gestion', 'label' => 'Gestión', 'type' => 'text'],
                    ['key' => 'total_postulaciones', 'label' => 'Postulaciones', 'type' => 'number'],
                    ['key' => 'admitidos', 'label' => 'Admitidos', 'type' => 'number'],
                    ['key' => 'no_admitidos', 'label' => 'No admitidos', 'type' => 'number'],
                    ['key' => 'pendientes', 'label' => 'Pendientes', 'type' => 'number'],
                    ['key' => 'pagos_completados', 'label' => 'Pagos completados', 'type' => 'number'],
                ],
                'sql' => <<<'SQL'
                    SELECT g.anio || '-' || g.semestre AS gestion,
                           COUNT(*) AS total_postulaciones,
                           COUNT(*) FILTER (WHERE p.estado_admision = 'admitido') AS admitidos,
                           COUNT(*) FILTER (WHERE p.estado_admision = 'no_admitido') AS no_admitidos,
                           COUNT(*) FILTER (WHERE p.estado_admision = 'pendiente') AS pendientes,
                           COUNT(*) FILTER (WHERE p.estado_pago = 'pagado') AS pagos_completados
                    FROM postulacion p
                    JOIN gestion g ON g.id = p.gestion_id
                    GROUP BY g.anio, g.semestre
                    ORDER BY g.anio DESC, g.semestre DESC
                    SQL,
            ],

            'admitidos_por_carrera' => [
                'label' => 'Admitidos por carrera',
                'descripcion' => 'Cantidad de admitidos y promedio general por carrera asignada en cada gestión.',
                'columnas' => [
                    ['key' => 'gestion', 'label' => 'Gestión', 'type' => 'text'],
                    ['key' => 'carrera', 'label' => 'Carrera', 'type' => 'text'],
                    ['key' => 'total_admitidos', 'label' => 'Admitidos', 'type' => 'number'],
                    ['key' => 'promedio', 'label' => 'Promedio general', 'type' => 'decimal'],
                ],
                'sql' => <<<'SQL'
                    SELECT g.anio || '-' || g.semestre AS gestion,
                           c.nombre AS carrera,
                           COUNT(*) AS total_admitidos,
                           ROUND(AVG(p.promedio_general), 2) AS promedio
                    FROM postulacion p
                    JOIN carrera c ON c.id = p.carrera_asignada_id
                    JOIN gestion g ON g.id = p.gestion_id
                    WHERE p.estado_admision = 'admitido'
                    GROUP BY g.anio, g.semestre, c.nombre
                    ORDER BY g.anio DESC, g.semestre DESC, total_admitidos DESC
                    SQL,
            ],

            'recaudacion_por_metodo' => [
                'label' => 'Recaudación por método de pago',
                'descripcion' => 'Cantidad de pagos y monto total en Bs agrupados por método y estado del pago.',
                'columnas' => [
                    ['key' => 'metodo', 'label' => 'Método', 'type' => 'badge'],
                    ['key' => 'estado', 'label' => 'Estado', 'type' => 'badge'],
                    ['key' => 'cantidad', 'label' => 'Cantidad', 'type' => 'number'],
                    ['key' => 'total_bs', 'label' => 'Total (Bs)', 'type' => 'decimal'],
                ],
                'sql' => <<<'SQL'
                    SELECT pg.metodo AS metodo,
                           pg.estado AS estado,
                           COUNT(*) AS cantidad,
                           ROUND(SUM(pg.monto_bs), 2) AS total_bs
                    FROM pago pg
                    GROUP BY pg.metodo, pg.estado
                    ORDER BY total_bs DESC
                    SQL,
            ],

            'top_promedios' => [
                'label' => 'Top 10 mejores promedios',
                'descripcion' => 'Los diez postulantes admitidos con mejor promedio general, con su carrera asignada.',
                'columnas' => [
                    ['key' => 'ci', 'label' => 'CI', 'type' => 'text'],
                    ['key' => 'estudiante', 'label' => 'Estudiante', 'type' => 'text'],
                    ['key' => 'carrera', 'label' => 'Carrera asignada', 'type' => 'text'],
                    ['key' => 'gestion', 'label' => 'Gestión', 'type' => 'text'],
                    ['key' => 'promedio_general', 'label' => 'Promedio', 'type' => 'decimal'],
                ],
                'sql' => <<<'SQL'
                    SELECT per.ci AS ci,
                           per.apellido || ' ' || per.nombres AS estudiante,
                           c.nombre AS carrera,
                           g.anio || '-' || g.semestre AS gestion,
                           p.promedio_general AS promedio_general
                    FROM postulacion p
                    JOIN candidato_estudiante ce ON ce.id = p.candidato_estudiante_id
                    JOIN persona per ON per.id = ce.persona_id
                    JOIN gestion g ON g.id = p.gestion_id
                    LEFT JOIN carrera c ON c.id = p.carrera_asignada_id
                    WHERE p.estado_admision = 'admitido' AND p.promedio_general IS NOT NULL
                    ORDER BY p.promedio_general DESC
                    LIMIT 10
                    SQL,
            ],

            'notas_por_materia' => [
                'label' => 'Rendimiento por materia',
                'descripcion' => 'Promedio, mínima y máxima de las notas registradas en cada materia.',
                'columnas' => [
                    ['key' => 'materia', 'label' => 'Materia', 'type' => 'text'],
                    ['key' => 'estudiantes', 'label' => 'Estudiantes', 'type' => 'number'],
                    ['key' => 'evaluaciones', 'label' => 'Evaluaciones', 'type' => 'number'],
                    ['key' => 'nota_promedio', 'label' => 'Promedio', 'type' => 'decimal'],
                    ['key' => 'nota_minima', 'label' => 'Mínima', 'type' => 'decimal'],
                    ['key' => 'nota_maxima', 'label' => 'Máxima', 'type' => 'decimal'],
                ],
                'sql' => <<<'SQL'
                    SELECT m.nombre AS materia,
                           COUNT(DISTINCT e.postulacion_id) AS estudiantes,
                           COUNT(*) AS evaluaciones,
                           ROUND(AVG(e.nota_cruda), 2) AS nota_promedio,
                           MIN(e.nota_cruda) AS nota_minima,
                           MAX(e.nota_cruda) AS nota_maxima
                    FROM evaluacion e
                    JOIN materia m ON m.codigo = e.codigo_materia
                    GROUP BY m.nombre
                    ORDER BY nota_promedio DESC
                    SQL,
            ],
        ];
    }

    /** @return array{label:string, descripcion:string, columnas:array, sql:string}|null */
    public function obtener(string $key): ?array
    {
        return $this->reportes[$key] ?? null;
    }

    /** Metadata de todos los reportes estáticos para alimentar la UI. */
    public function meta(): array
    {
        return collect($this->reportes)
            ->map(fn ($def, $key) => [
                'key' => $key,
                'label' => $def['label'],
                'descripcion' => $def['descripcion'],
                'sql' => $def['sql'],
            ])
            ->values()
            ->all();
    }

    /**
     * Ejecuta la consulta SQL del reporte tal cual está escrita.
     *
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function ejecutar(string $key): array
    {
        $def = $this->obtener($key);

        if (! $def) {
            return ['rows' => [], 'total' => 0];
        }

        $rows = array_map(fn ($r) => (array) $r, DB::select($def['sql']));

        return ['rows' => $rows, 'total' => count($rows)];
    }
}
