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

echo "==> Ejecutando migraciones..."
php artisan migrate --force

echo "==> Ejecutando seeders (solo si la tabla de usuarios está vacía)..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1)
if [ "${USER_COUNT}" = "0" ] || [ -z "${USER_COUNT}" ]; then
    php artisan db:seed --force
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
