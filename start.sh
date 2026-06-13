#!/usr/bin/env bash
set -e

echo "==> Preparando directorios de storage..."
mkdir -p storage/app/public \
         storage/framework/cache \
         storage/framework/sessions \
         storage/framework/views \
         storage/logs
chmod -R 775 storage bootstrap/cache

echo "==> Creando enlace simbólico de storage..."
php artisan storage:link --force 2>/dev/null || true

echo "==> Esperando a que la base de datos esté disponible..."
# La red privada de Railway (IPv6) puede tardar unos segundos en estar lista
# al arrancar el contenedor. Reintentamos la conexión antes de migrar.
DB_READY=0
for i in $(seq 1 30); do
    if php artisan db:show >/dev/null 2>&1; then
        DB_READY=1
        echo "    Base de datos disponible (intento ${i})."
        break
    fi
    echo "    Intento ${i}/30: la base de datos aún no responde, reintentando en 2s..."
    sleep 2
done

if [ "${DB_READY}" != "1" ]; then
    echo "!!! No se pudo conectar a la base de datos tras 60s."
    echo "!!! Revisa las variables DB_* en Railway (host de red privada/proxy)."
    exit 1
fi

echo "==> Ejecutando migraciones..."
php artisan migrate --force

echo "==> Ejecutando seeders (solo si la tabla de usuarios está vacía)..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
    php artisan db:seed --force || echo "!!! El seeder falló, continuando con el arranque."
    echo "    Seeders ejecutados."
else
    echo "    BD ya tiene datos, se omite el seeder."
fi

echo "==> Cacheando configuración para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "==> Iniciando queue worker en segundo plano..."
php artisan queue:work --tries=3 --sleep=3 --max-time=3600 &

echo "==> Iniciando servidor web en puerto ${PORT:-8000}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
