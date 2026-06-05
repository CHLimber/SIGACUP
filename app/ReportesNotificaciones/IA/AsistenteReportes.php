<?php

namespace App\ReportesNotificaciones\IA;

use App\ReportesNotificaciones\ReporteRegistry;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Traduce una consulta en lenguaje natural (texto o voz transcrita) a una
 * consulta estructurada sobre los reportes existentes.
 *
 * Diseño de seguridad: la IA NUNCA genera SQL. Recibe el catálogo de reportes
 * (claves, columnas, filtros y dimensiones disponibles) y debe devolver un JSON
 * que selecciona un reporte y rellena sus filtros. Ese JSON se ejecuta luego con
 * el mismo AbstractReport que valida y parametriza todo → sin riesgo de inyección.
 */
class AsistenteReportes
{
    private const ENDPOINT = 'https://api.anthropic.com/v1/messages';

    public function __construct(private readonly ReporteRegistry $registry) {}

    /**
     * Interpreta la consulta y devuelve los parámetros del reporte a ejecutar.
     *
     * @return array{reporte:string, filtros:array, columnas:array, sort:?string, dir:string, dimension:?string, explicacion:string}
     *
     * @throws RuntimeException si no hay API key, la API falla o la respuesta es inválida.
     */
    public function interpretar(string $consulta): array
    {
        $apiKey = config('services.anthropic.key');

        if (empty($apiKey)) {
            throw new RuntimeException('El asistente de IA no está configurado. Falta ANTHROPIC_API_KEY en el archivo .env.');
        }

        $respuesta = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => config('services.anthropic.version', '2023-06-01'),
            'content-type' => 'application/json',
        ])->timeout(45)->post(self::ENDPOINT, [
            'model' => config('services.anthropic.model'),
            'max_tokens' => 1024,
            'system' => $this->systemPrompt(),
            'tools' => [$this->toolDefinition()],
            'tool_choice' => ['type' => 'tool', 'name' => 'generar_reporte'],
            'messages' => [
                ['role' => 'user', 'content' => $consulta],
            ],
        ]);

        if ($respuesta->failed()) {
            $detalle = $respuesta->json('error.message') ?? 'Error desconocido';
            throw new RuntimeException('La IA no pudo procesar la consulta: '.$detalle);
        }

        $toolUse = collect($respuesta->json('content', []))
            ->firstWhere('type', 'tool_use');

        if (! $toolUse || ! isset($toolUse['input'])) {
            throw new RuntimeException('La IA no devolvió una consulta interpretable. Reformulá tu pedido.');
        }

        return $this->normalizar($toolUse['input']);
    }

    /** Catálogo de reportes serializado para que el modelo conozca qué puede consultar. */
    private function catalogo(): array
    {
        return collect($this->registry->meta())->map(fn ($r) => [
            'key' => $r['key'],
            'nombre' => $r['label'],
            'descripcion' => $r['descripcion'],
            'columnas' => collect($r['columnas'])->map(fn ($c) => $c['key'].' ('.$c['label'].')')->all(),
            'filtros' => collect($r['filtros'])->map(fn ($f) => [
                'key' => $f['key'],
                'label' => $f['label'],
                'type' => $f['type'],
                'options' => collect($f['options'] ?? [])->map(fn ($o) => $o['value'].'='.$o['label'])->all(),
            ])->all(),
            'dimensiones' => collect($r['dimensiones'])->map(fn ($d) => $d['key'].' ('.$d['label'].')')->all(),
        ])->all();
    }

    private function systemPrompt(): string
    {
        $catalogo = json_encode($this->catalogo(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
        Eres el asistente de reportes del sistema SIGACUP (admisión universitaria del CUP de la FICCT).
        Tu tarea: traducir una consulta en lenguaje natural del usuario (en español) a los parámetros de
        UNO de los reportes disponibles, usando la herramienta `generar_reporte`.

        Reglas:
        - Elegí el reporte (`reporte`) cuya `key` mejor responda a la intención del usuario.
        - Rellená `filtros` SOLO con keys de filtro que existan en el reporte elegido. Para filtros de
          tipo `select`, usá el `value` (lado izquierdo del `=`), no la etiqueta. Para `text` usá texto libre.
          Para rangos (`numberrange`/`daterange`) usá objetos {min,max} o {desde,hasta}.
        - Si el usuario menciona un nombre de profesor, carrera o materia, mapéalo al filtro o búsqueda
          correspondiente del reporte.
        - `dimension` debe ser una key de dimensión válida del reporte (para el gráfico).
        - `explicacion`: una frase breve en español explicando qué vas a mostrar.
        - Si la consulta no corresponde a ningún reporte, elegí el más cercano y acláralo en la explicación.

        Catálogo de reportes disponibles:
        {$catalogo}
        PROMPT;
    }

    private function toolDefinition(): array
    {
        return [
            'name' => 'generar_reporte',
            'description' => 'Genera un reporte del sistema a partir de la intención del usuario.',
            'input_schema' => [
                'type' => 'object',
                'properties' => [
                    'reporte' => ['type' => 'string', 'description' => 'key del reporte a usar'],
                    'filtros' => ['type' => 'object', 'description' => 'mapa key→valor de filtros del reporte'],
                    'columnas' => ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'keys de columnas a mostrar (vacío = todas)'],
                    'sort' => ['type' => 'string', 'description' => 'key de columna por la que ordenar'],
                    'dir' => ['type' => 'string', 'enum' => ['asc', 'desc']],
                    'dimension' => ['type' => 'string', 'description' => 'key de dimensión para agrupar el gráfico'],
                    'explicacion' => ['type' => 'string', 'description' => 'frase breve sobre lo que muestra el reporte'],
                ],
                'required' => ['reporte', 'explicacion'],
            ],
        ];
    }

    /** Valida y normaliza la salida del modelo contra el registry. */
    private function normalizar(array $input): array
    {
        $reporte = $this->registry->obtener($input['reporte'] ?? '');

        if (! $reporte) {
            throw new RuntimeException('La IA eligió un reporte que no existe. Reformulá tu pedido.');
        }

        return [
            'reporte' => $reporte->key(),
            'filtros' => is_array($input['filtros'] ?? null) ? $input['filtros'] : [],
            'columnas' => is_array($input['columnas'] ?? null) ? array_values(array_filter($input['columnas'])) : [],
            'sort' => $input['sort'] ?? null,
            'dir' => ($input['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            'dimension' => $input['dimension'] ?? null,
            'explicacion' => (string) ($input['explicacion'] ?? 'Reporte generado a partir de tu consulta.'),
        ];
    }
}
