<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resumen estadístico</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            color: #1f2937;
            margin: 0;
            padding: 0;
            font-size: 11px;
        }
        .header { background: #073b75; color: #fff; padding: 16px 24px; }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 3px 0 0; font-size: 10px; opacity: 0.85; }
        .seccion { padding: 14px 24px 0; }
        .seccion h2 {
            margin: 0 0 8px;
            color: #073b75;
            font-size: 13px;
            border-bottom: 2px solid #073b75;
            padding-bottom: 3px;
        }
        .kpis { width: 100%; border-collapse: collapse; }
        .kpis td {
            width: 16.6%;
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: center;
        }
        .kpis .label { font-size: 8px; text-transform: uppercase; color: #6b7280; letter-spacing: 0.4px; }
        .kpis .valor { font-size: 16px; font-weight: 700; color: #073b75; margin-top: 3px; }
        table.datos { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.datos thead th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            padding: 6px 8px;
            font-size: 9px;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
        }
        table.datos tbody td {
            padding: 5px 8px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10px;
        }
        table.datos tbody tr:nth-child(even) { background: #fafafa; }
        .c { text-align: center; }
        .r { text-align: right; }
        .pie {
            margin-top: 18px;
            padding: 10px 24px;
            font-size: 9px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
    </style>
</head>
<body>
    @php $k = $resumen['kpis']; @endphp
    <div class="header">
        <h1>SIGACUP — FICCT UAGRM</h1>
        <p>Resumen estadístico · {{ $gestionLabel }} · Generado el {{ $fecha }}</p>
    </div>

    <div class="seccion">
        <h2>Indicadores generales</h2>
        <table class="kpis">
            <tr>
                <td><div class="label">Postulaciones</div><div class="valor">{{ $k['postulaciones'] }}</div></td>
                <td><div class="label">Aprobados CUP</div><div class="valor">{{ $k['aprobados_cup'] }}</div></td>
                <td><div class="label">Reprobados CUP</div><div class="valor">{{ $k['reprobados_cup'] }}</div></td>
                <td><div class="label">Admitidos</div><div class="valor">{{ $k['admitidos'] }}</div></td>
                <td><div class="label">Tasa admisión</div><div class="valor">{{ $k['tasa_admision'] }}%</div></td>
                <td><div class="label">Promedio</div><div class="valor">{{ $k['promedio_general'] ?? '—' }}</div></td>
            </tr>
            <tr>
                <td><div class="label">Grupos habilitados</div><div class="valor">{{ $k['grupos'] }}</div></td>
                <td><div class="label">Docentes</div><div class="valor">{{ $k['docentes'] }}</div></td>
                <td><div class="label">Recaudación Bs</div><div class="valor">{{ number_format($k['recaudacion_bs'], 2) }}</div></td>
                <td><div class="label">Pagos OK</div><div class="valor">{{ $k['pagos_pagados'] }}</div></td>
                <td><div class="label">Pagos pend.</div><div class="valor">{{ $k['pagos_pendientes'] }}</div></td>
                <td><div class="label">Pendientes</div><div class="valor">{{ $k['pendientes'] }}</div></td>
            </tr>
        </table>
    </div>

    <div class="seccion">
        <h2>Admisión por carrera</h2>
        <table class="datos">
            <thead>
                <tr>
                    <th>Carrera</th><th class="c">1ª opción</th><th class="c">Admitidos</th>
                    <th class="c">Cupo</th><th class="c">Ocupación</th><th class="c">Promedio</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resumen['porCarrera'] as $fila)
                    <tr>
                        <td>{{ $fila['carrera'] }}</td>
                        <td class="c">{{ $fila['primera_opcion'] }}</td>
                        <td class="c">{{ $fila['admitidos'] }}</td>
                        <td class="c">{{ $fila['cupo'] }}</td>
                        <td class="c">{{ $fila['ocupacion'] !== null ? $fila['ocupacion'].'%' : '—' }}</td>
                        <td class="c">{{ $fila['promedio'] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <h2>Comparativa por gestión</h2>
        <table class="datos">
            <thead>
                <tr>
                    <th>Gestión</th><th>Estado</th><th class="c">Postulaciones</th><th class="c">Admitidos</th>
                    <th class="c">Tasa admisión</th><th class="c">Pagos</th><th class="r">Recaudación (Bs)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resumen['porGestion'] as $fila)
                    <tr>
                        <td>{{ $fila['gestion'] }}</td>
                        <td>{{ ucfirst($fila['estado']) }}</td>
                        <td class="c">{{ $fila['postulaciones'] }}</td>
                        <td class="c">{{ $fila['admitidos'] }}</td>
                        <td class="c">{{ $fila['tasa_admision'] }}%</td>
                        <td class="c">{{ $fila['pagos'] }}</td>
                        <td class="r">{{ number_format($fila['recaudacion'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <h2>Estadísticas por materia</h2>
        <table class="datos">
            <thead>
                <tr>
                    <th>Materia</th><th class="c">Estudiantes</th><th class="c">Aprobados</th>
                    <th class="c">Reprobados</th><th class="c">% Aprob.</th>
                    <th class="c">Promedio</th><th class="c">Máxima</th><th class="c">Mínima</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($resumen['porMateria'] as $fila)
                    <tr>
                        <td>{{ $fila['materia'] }}</td>
                        <td class="c">{{ $fila['estudiantes'] }}</td>
                        <td class="c">{{ $fila['aprobados'] }}</td>
                        <td class="c">{{ $fila['reprobados'] }}</td>
                        <td class="c">{{ $fila['tasa_aprobacion'] }}%</td>
                        <td class="c">{{ $fila['promedio'] }}</td>
                        <td class="c">{{ $fila['maxima'] }}</td>
                        <td class="c">{{ $fila['minima'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <h2>Grupos con mayor cantidad de aprobados</h2>
        <table class="datos">
            <thead>
                <tr>
                    <th>Gestión</th><th>Materia</th><th>Grupo</th><th>Turno</th><th>Docente(s)</th>
                    <th class="c">Inscritos</th><th class="c">Aprobados</th><th class="c">Reprob.</th><th class="c">% Aprob.</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($resumen['topGrupos'] as $fila)
                    <tr>
                        <td>{{ $fila['gestion'] }}</td>
                        <td>{{ $fila['materia'] }}</td>
                        <td>{{ $fila['grupo'] }}</td>
                        <td>{{ $fila['turno'] }}</td>
                        <td>{{ $fila['docentes'] ?? '—' }}</td>
                        <td class="c">{{ $fila['inscritos'] }}</td>
                        <td class="c">{{ $fila['aprobados'] }}</td>
                        <td class="c">{{ $fila['reprobados'] }}</td>
                        <td class="c">{{ $fila['pct_aprobados'] ?? '—' }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="c">Sin grupos con calificaciones.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="seccion">
        <h2>Docentes con mayor % de aprobados</h2>
        <table class="datos">
            <thead>
                <tr>
                    <th>Docente</th><th>Materia</th><th>Grupo</th><th>Gestión</th>
                    <th class="c">Inscritos</th><th class="c">Aprobados</th><th class="c">% Aprob.</th><th class="c">Promedio</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($resumen['topDocentes'] as $fila)
                    <tr>
                        <td>{{ $fila['docente'] }}</td>
                        <td>{{ $fila['materia'] }}</td>
                        <td>{{ $fila['grupo'] }}</td>
                        <td>{{ $fila['gestion'] }}</td>
                        <td class="c">{{ $fila['inscritos'] }}</td>
                        <td class="c">{{ $fila['aprobados'] }}</td>
                        <td class="c">{{ $fila['pct_aprobados'] ?? '—' }}%</td>
                        <td class="c">{{ $fila['promedio'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="c">Sin docentes con grupos calificados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pie">
        Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones — UAGRM ·
        Santa Cruz de la Sierra, Bolivia
    </div>
</body>
</html>
