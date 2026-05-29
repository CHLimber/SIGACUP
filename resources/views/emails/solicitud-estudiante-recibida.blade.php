<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Solicitud recibida</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #073b75; margin-top: 0;">¡Hola {{ $candidato->nombres }}!</h2>

        <p>Hemos recibido tu solicitud de inscripción al <strong>Curso Preuniversitario (CUP)</strong> de la FICCT.</p>

        <div style="background: #f3f4f6; padding: 15px; border-left: 4px solid #c70e0a; margin: 20px 0;">
            <p style="margin: 0 0 6px;"><strong>Tus datos registrados:</strong></p>
            <p style="margin: 2px 0;"><strong>CI:</strong> {{ $candidato->ci }}</p>
            <p style="margin: 2px 0;"><strong>Nombre completo:</strong> {{ $candidato->apellido }} {{ $candidato->nombres }}</p>
            <p style="margin: 2px 0;"><strong>1ra opción:</strong> {{ ucfirst($candidato->carrera_primera_opcion) }}</p>
            <p style="margin: 2px 0;"><strong>2da opción:</strong> {{ ucfirst($candidato->carrera_segunda_opcion) }}</p>
        </div>

        <p>Para continuar, debes <strong>subir tus requisitos</strong> (CI, certificado de nacimiento, diploma de bachiller, foto carnet) en tu portal personal.</p>

        <div style="text-align: center; margin: 28px 0;">
            <a
                href="{{ route('portal.candidato.show', ['token' => $candidato->token_acceso]) }}"
                style="display: inline-block; background: #c70e0a; color: white; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;"
            >
                Subir mis requisitos
            </a>
        </div>

        <p style="font-size: 12px; color: #888; word-break: break-all;">
            Si el botón no funciona, copia este enlace en tu navegador:<br>
            <span style="color: #073b75;">{{ route('portal.candidato.show', ['token' => $candidato->token_acceso]) }}</span>
        </p>

        <p style="background: #fff7e6; border-left: 4px solid #f59e0b; padding: 10px 14px; font-size: 13px; color: #92400e; margin: 18px 0;">
            <strong>Importante:</strong> este enlace es personal y único. No lo compartas con nadie. Lo necesitarás también si tus requisitos son rechazados, para volver a subirlos.
        </p>

        <p>Una vez que envíes tu documentación, la coordinación la revisará y te notificará por este mismo correo.</p>

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
