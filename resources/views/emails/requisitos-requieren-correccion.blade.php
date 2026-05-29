<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Tu solicitud requiere correcciones</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; opacity: 0.85;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #c70e0a; margin-top: 0;">Hola {{ $candidato->nombres }},</h2>

        <p>
            Hemos revisado los requisitos que enviaste y <strong>algunos documentos no cumplen con los criterios</strong>.
            A continuación encontrarás el detalle por cada documento rechazado, con la observación que debes corregir.
        </p>

        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 10px; font-weight: 600; color: #9a3412;">Documentos a corregir:</p>
            @foreach ($items as $item)
                <div style="background: #fff; border: 1px solid #fed7aa; border-radius: 6px; padding: 12px; margin-bottom: 10px;">
                    <p style="margin: 0 0 4px; font-weight: 600; color: #9a3412;">✗ {{ $item['nombre'] }}</p>
                    <p style="margin: 0; font-size: 13px; color: #7c2d12;"><strong>Motivo:</strong> {{ $item['motivo'] }}</p>
                </div>
            @endforeach
        </div>

        <p>Vuelve a tu portal personal, sube los archivos corregidos y envía nuevamente la solicitud:</p>

        <div style="text-align: center; margin: 28px 0;">
            <a
                href="{{ $portalUrl }}"
                style="display: inline-block; background: #c70e0a; color: white; padding: 12px 28px; border-radius: 6px; text-decoration: none; font-weight: 600;"
            >
                Corregir mis requisitos
            </a>
        </div>

        <p style="font-size: 12px; color: #888; word-break: break-all;">
            Si el botón no funciona, copia este enlace:<br>
            <span style="color: #073b75;">{{ $portalUrl }}</span>
        </p>

        <p style="font-size: 13px; color: #666; margin-top: 20px;">
            Los documentos ya aprobados se mantienen y no necesitas volver a subirlos.
            Solo se desbloquean los que aparecen en la lista de arriba.
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
