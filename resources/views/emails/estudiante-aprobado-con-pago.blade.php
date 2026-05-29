<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud aprobada — Pago de matrícula</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #16a34a; margin-top: 0;">🎉 ¡Felicidades, {{ $candidato->nombres }}!</h2>

        <p>Tu solicitud al <strong>Curso Preuniversitario (CUP)</strong> ha sido <strong style="color: #16a34a;">aprobada</strong>. El último paso es completar el pago de tu matrícula.</p>

        <div style="background: #f0fdf4; padding: 15px; border-left: 4px solid #16a34a; margin: 20px 0;">
            <p style="margin: 0 0 6px;"><strong>Datos confirmados:</strong></p>
            <p style="margin: 2px 0;"><strong>CI:</strong> {{ $candidato->ci }}</p>
            <p style="margin: 2px 0;"><strong>Nombre:</strong> {{ $candidato->apellido }} {{ $candidato->nombres }}</p>
            <p style="margin: 2px 0;"><strong>1ra opción:</strong> {{ ucfirst($candidato->carrera_primera_opcion) }}</p>
        </div>

        <div style="background: #fffbeb; padding: 18px; border: 1px solid #fde68a; border-radius: 8px; margin: 20px 0;">
            <p style="margin: 0 0 8px; font-weight: 600; color: #92400e;">Detalle de la matrícula:</p>
            <table style="width: 100%; font-size: 14px;">
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;">Monto en bolivianos:</td>
                    <td style="padding: 4px 0; text-align: right; font-weight: 600;">Bs {{ number_format($candidato->monto_bs, 2) }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280;">A cobrar (USD):</td>
                    <td style="padding: 4px 0; text-align: right; font-weight: 700; color: #c70e0a; font-size: 16px;">${{ number_format($candidato->monto_usd, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 6px; font-size: 11px; color: #92400e; border-top: 1px dashed #fde68a;">
                        Tasa de conversión: 1 USD = {{ number_format($candidato->tasa_cambio, 4) }} Bs
                    </td>
                </tr>
            </table>
        </div>

        <p>El pago se procesa con <strong>Stripe</strong>, una pasarela segura internacional. Aceptamos tarjetas de crédito y débito.</p>

        <div style="text-align: center; margin: 28px 0;">
            <a
                href="{{ $pagoUrl }}"
                style="display: inline-block; background: #c70e0a; color: white; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 700; font-size: 15px;"
            >
                Pagar matrícula ahora
            </a>
        </div>

        <p style="font-size: 12px; color: #888; word-break: break-all;">
            Si el botón no funciona, copia este enlace en tu navegador:<br>
            <span style="color: #073b75;">{{ $pagoUrl }}</span>
        </p>

        <p style="background: #fff7e6; border-left: 4px solid #f59e0b; padding: 10px 14px; font-size: 13px; color: #92400e; margin: 18px 0;">
            <strong>Importante:</strong> este enlace es personal y único. No lo compartas con nadie.
            Una vez confirmado el pago, recibirás un nuevo correo con tus <strong>credenciales de acceso</strong> al sistema
            y el <strong>comprobante de tu matrícula</strong>.
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
