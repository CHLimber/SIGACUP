<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resolución de tu solicitud</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px; color: #ffffff;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; color: #d0e4f7;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #c70e0a; margin-top: 0;">Hola {{ $nombreCompleto }},</h2>

        <p>
            Lamentamos informarte que tu solicitud
            @if ($tipo === 'docente')
                de incorporación como <strong>docente</strong>
            @else
                de admisión al <strong>Curso Preuniversitario (CUP)</strong>
            @endif
            ha sido <strong style="color: #c70e0a;">rechazada definitivamente</strong>.
        </p>

        @if ($ci)
            <div style="background: #fef2f2; border-left: 4px solid #c70e0a; padding: 12px 14px; margin: 18px 0;">
                <p style="margin: 0; font-size: 14px; color: #7f1d1d;">
                    <strong>CI:</strong> {{ $ci }}
                </p>
            </div>
        @endif

        <div style="background: #fff7ed; border-left: 4px solid #f97316; padding: 15px; margin: 20px 0;">
            <p style="margin: 0 0 6px; font-weight: 600; color: #9a3412;">Motivo del rechazo:</p>
            <p style="margin: 0; color: #7c2d12;">{{ $motivo }}</p>
        </div>

        <p style="font-size: 14px; color: #555;">
            Por motivos de protección de datos, tu información y los archivos que habías subido han sido eliminados de
            nuestro sistema. Si en el futuro deseas volver a postular, deberás iniciar una nueva solicitud desde cero.
        </p>

        <p style="font-size: 13px; color: #666; margin-top: 24px;">
            Si consideras que esta decisión es un error, puedes comunicarte con la Coordinación del CUP para más información.
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
