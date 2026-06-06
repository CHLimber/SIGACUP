<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Credenciales de acceso</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: linear-gradient(to right, #060041, #073b75); padding: 20px; color: white; border-radius: 8px 8px 0 0;">
        <h1 style="margin: 0; font-size: 22px; color: #ffffff;">SIGACUP — FICCT UAGRM</h1>
        <p style="margin: 4px 0 0; font-size: 13px; color: #d0e4f7;">Sistema de Gestión del Curso Preuniversitario</p>
    </div>

    <div style="background: #fff; padding: 30px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <h2 style="color: #073b75; margin-top: 0;">Hola, {{ $nombre }}</h2>

        <p>Se ha creado una cuenta para vos en el sistema SIGACUP. A continuación encontrarás tus credenciales de acceso:</p>

        <div style="background: #fef3c7; padding: 20px; border-left: 4px solid #c70e0a; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0 0 12px; font-size: 14px;"><strong>Tus credenciales:</strong></p>
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
                <li>Por seguridad, no respondas a este correo con tu contraseña.</li>
            </ul>
        </div>

        <p style="text-align: center; margin: 25px 0;">
            <a href="{{ config('app.url') }}/login"
               style="background: #c70e0a; color: white; padding: 12px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Acceder al Sistema
            </a>
        </p>

        <p style="margin-top: 30px; font-size: 13px; color: #666;">
            Atentamente,<br>
            <strong>Administración SIGACUP — FICCT UAGRM</strong>
        </p>
    </div>

    <p style="text-align: center; font-size: 11px; color: #999; margin-top: 20px;">
        Este es un mensaje automático del Sistema SIGACUP. Por favor no respondas a este correo.
    </p>
</body>
</html>
