<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de matrícula — {{ $pago->numero_factura }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            margin: 0;
            padding: 32px 16px;
        }
        .factura {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 12px 32px rgba(6, 0, 65, 0.15);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(to right, #060041, #073b75);
            color: #fff;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .header h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.5px;
        }
        .header p {
            margin: 4px 0 0;
            font-size: 12px;
            opacity: 0.85;
        }
        .badge-pagado {
            background: #16a34a;
            color: #fff;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .titulo-factura {
            padding: 28px 32px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .titulo-factura h2 {
            margin: 0;
            color: #073b75;
            font-size: 20px;
        }
        .titulo-factura .meta {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 13px;
            color: #6b7280;
        }
        .titulo-factura .meta strong { color: #111827; }
        .grupo {
            padding: 20px 32px;
            border-bottom: 1px solid #f3f4f6;
        }
        .grupo h3 {
            margin: 0 0 8px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #9ca3af;
        }
        .grupo .fila {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            margin: 4px 0;
            font-size: 13px;
        }
        .grupo .fila .label { color: #6b7280; }
        .grupo .fila .valor { color: #111827; font-weight: 500; }
        .totales {
            padding: 20px 32px;
            background: #f9fafb;
        }
        .totales .total {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            padding: 10px 0;
            border-top: 2px solid #073b75;
            margin-top: 8px;
        }
        .totales .total .label { font-size: 13px; color: #6b7280; }
        .totales .total .valor {
            font-size: 26px;
            font-weight: 700;
            color: #c70e0a;
        }
        .pie {
            padding: 16px 32px;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }
        .pie strong { color: #073b75; }
        .acciones {
            text-align: center;
            margin-top: 24px;
        }
        .btn-imprimir {
            display: inline-block;
            background: #073b75;
            color: #fff;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            padding: 10px 22px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
        }
        .btn-imprimir:hover { background: #052a55; }
        @media print {
            body { background: #fff; padding: 0; }
            .factura { box-shadow: none; border-radius: 0; }
            .acciones { display: none; }
        }
    </style>
</head>
<body>
    @php
        $persona   = $pago->postulacion->candidatoEstudiante->persona;
        $carrera1  = $pago->postulacion->carrera1;
    @endphp

    <div class="factura">
        <div class="header">
            <div>
                <h1>SIGACUP — FICCT UAGRM</h1>
                <p>Sistema de Gestión del Curso Preuniversitario</p>
                <p>Av. Busch · Santa Cruz de la Sierra · Bolivia</p>
            </div>
            <span class="badge-pagado">✓ Pagado</span>
        </div>

        <div class="titulo-factura">
            <h2>Comprobante de matrícula</h2>
            <div class="meta">
                <span>Nº <strong>{{ $pago->numero_factura }}</strong></span>
                <span>Fecha: <strong>{{ $pago->updated_at?->format('d/m/Y H:i') }}</strong></span>
            </div>
        </div>

        <div class="grupo">
            <h3>Datos del estudiante</h3>
            <div class="fila">
                <span class="label">Nombre completo</span>
                <span class="valor">{{ $persona->apellido }} {{ $persona->nombres }}</span>
            </div>
            <div class="fila">
                <span class="label">CI</span>
                <span class="valor">{{ $persona->ci }}</span>
            </div>
            <div class="fila">
                <span class="label">Email</span>
                <span class="valor">{{ $persona->email }}</span>
            </div>
            <div class="fila">
                <span class="label">Carrera (1ra opción)</span>
                <span class="valor">{{ $carrera1?->nombre }}</span>
            </div>
        </div>

        <div class="grupo">
            <h3>Detalle</h3>
            <div class="fila">
                <span class="label">Concepto</span>
                <span class="valor">{{ config('sigacup.matricula.descripcion') }}</span>
            </div>
            <div class="fila">
                <span class="label">Monto</span>
                <span class="valor">Bs {{ number_format($pago->monto_bs, 2) }}</span>
            </div>
            <div class="fila">
                <span class="label">Método de pago</span>
                <span class="valor">Tarjeta (Stripe)</span>
            </div>
            <div class="fila">
                <span class="label">ID transacción Stripe</span>
                <span class="valor" style="font-family: monospace; font-size: 11px;">{{ $pago->stripe_payment_intent_id }}</span>
            </div>
        </div>

        <div class="totales">
            <div class="total">
                <span class="label">Total cobrado</span>
                <span class="valor">Bs {{ number_format($pago->monto_bs, 2) }}</span>
            </div>
        </div>

        <div class="pie">
            Este comprobante certifica el pago de la matrícula del Curso Preuniversitario de la
            <strong>Facultad de Ingeniería en Ciencias de la Computación y Telecomunicaciones — UAGRM</strong>.
            Conserva este documento para cualquier trámite posterior.
        </div>
    </div>

    <div class="acciones">
        <button class="btn-imprimir" onclick="window.print()">🖨 Imprimir comprobante</button>
    </div>
</body>
</html>
