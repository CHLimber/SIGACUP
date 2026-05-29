<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago confirmado — Credenciales de acceso</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #16a34a; margin-top: 0;">✓ ¡Pago confirmado, {{ $candidato->nombres }}!</h2>

        <p>Recibimos tu pago de matrícula correctamente. <strong>Bienvenido al Curso Preuniversitario de la FICCT.</strong></p>

        <div style="background: #f0fdf4; padding: 15px; border-left: 4px solid #16a34a; margin: 20px 0; font-size: 13px;">
            <p style="margin: 2px 0;"><strong>Factura Nº:</strong> {{ $candidato->numero_factura }}</p>
            <p style="margin: 2px 0;"><strong>Monto pagado:</strong> ${{ number_format($candidato->monto_usd, 2) }} USD (equiv. Bs {{ number_format($candidato->monto_bs, 2) }})</p>
            <p style="margin: 2px 0;"><strong>Fecha:</strong> {{ optional($candidato->pagado_at)->format('d/m/Y H:i') }}</p>
        </div>

        <h3 style="color: #073b75; margin: 24px 0 8px;">🔐 Tus credenciales de acceso</h3>

        <div style="background: #fef3c7; padding: 20px; border-left: 4px solid #c70e0a; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 4px 0; font-family: 'Courier New', monospace; font-size: 16px;">
                <strong>Usuario:</strong> {{ $username }}
            </p>
            <p style="margin: 4px 0; font-family: 'Courier New', monospace; font-size: 16px;">
                <strong>Contraseña temporal:</strong> {{ $passwordTemporal }}
            </p>
        </div>

        <div style="background: #fff7ed; padding: 15px; border-left: 4px solid #f97316; margin: 20px 0; font-size: 13px;">
            <strong>⚠️ Importante:</strong>
            <ul style="margin: 6px 0; padding-left: 18px;">
                <li>Esta contraseña es <strong>temporal</strong>. Cámbiala al iniciar sesión por primera vez.</li>
                <li>No compartas estas credenciales con nadie.</li>
            </ul>
        </div>

        <div style="text-align: center; margin: 25px 0;">
            <a href="{{ config('app.url') }}/login"
               style="background: #c70e0a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Acceder al Sistema
            </a>
        </div>

        <h3 style="color: #073b75; margin: 24px 0 8px;">📄 Comprobante de matrícula</h3>

        <p>Puedes ver e imprimir tu comprobante de pago aquí:</p>

        <div style="text-align: center; margin: 18px 0;">
            <a href="{{ $comprobanteUrl }}"
               style="background: #073b75; color: white; padding: 10px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; font-size: 14px;">
                Ver / Imprimir comprobante
            </a>
        </div>

        <p style="font-size: 12px; color: #888; word-break: break-all;">
            Enlace directo: <span style="color: #073b75;">{{ $comprobanteUrl }}</span>
        </p>

        <p style="margin-top: 30px; font-size: 13px; color: #666;">
            Atentamente,<br>
            <strong>Coordinación del CUP — FICCT UAGRM</strong>
        </p>
    </div>

    <p style="text-align: center; font-size: 11px; color: #999; margin-top: 20px;">
        Este es un mensaje automático del Sistema SIGACUP. Por favor no respondas a este correo.
    </p>
</body>
</html>
