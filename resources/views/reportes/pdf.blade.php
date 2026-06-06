<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
        .header {
            background: #073b75;
            color: #fff;
            padding: 16px 24px;
        }
        .header h1 { margin: 0; font-size: 16px; letter-spacing: 0.5px; }
        .header p { margin: 3px 0 0; font-size: 10px; opacity: 0.85; }
        .meta {
            padding: 12px 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .meta h2 { margin: 0 0 4px; color: #073b75; font-size: 14px; }
        .meta .descripcion { margin: 0; color: #6b7280; font-size: 10px; }
        .meta .info {
            margin-top: 8px;
            font-size: 10px;
            color: #374151;
        }
        .meta .info strong { color: #111827; }
        .filtros {
            margin-top: 6px;
            font-size: 10px;
            color: #6b7280;
        }
        .filtros .chip {
            display: inline-block;
            background: #f3f4f6;
            border-radius: 4px;
            padding: 2px 6px;
            margin: 2px 2px 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        thead th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 7px 8px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            border-bottom: 2px solid #073b75;
        }
        tbody td {
            padding: 6px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10px;
        }
        tbody tr:nth-child(even) { background: #fafafa; }
        td.num { text-align: right; }
        .vacio {
            text-align: center;
            padding: 24px;
            color: #9ca3af;
        }
        .pie {
            margin-top: 16px;
            padding: 10px 24px;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>SIGACUP — FICCT UAGRM</h1>
        <p>Sistema de Gestión del Curso Preuniversitario · Reporte generado el {{ $fecha }}</p>
    </div>

    <div class="meta">
        <h2>{{ $titulo }}</h2>
        <p class="descripcion">{{ $descripcion }}</p>
        <div class="info"><strong>{{ $total }}</strong> registro(s)</div>
        @if (count($filtros))
            <div class="filtros">
                Filtros:
                @foreach ($filtros as $f)
                    <span class="chip"><strong>{{ $f['label'] }}:</strong> {{ $f['valor'] }}</span>
                @endforeach
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($columnas as $col)
                    <th>{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($columnas as $col)
                        @php
                            $valor = $row[$col['key']] ?? null;
                            $esNum = in_array($col['type'], ['number', 'decimal'], true);
                            if (is_bool($valor)) {
                                $valor = $valor ? 'Sí' : 'No';
                            }
                            if ($valor === null || $valor === '') {
                                $valor = '—';
                            }
                        @endphp
                        <td class="{{ $esNum ? 'num' : '' }}">{{ $valor }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="vacio" colspan="{{ count($columnas) }}">
                        No hay registros que coincidan con los filtros.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones — UAGRM ·
        Santa Cruz de la Sierra, Bolivia
    </div>
</body>
</html>
