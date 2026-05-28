# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Laravel 13 + Inertia.js v3 + Vue 3 + TypeScript + Tailwind CSS v4 + PostgreSQL. Auth manejada por Laravel Fortify con soporte para passkeys y 2FA (via `laravel/chisel`). Las rutas tipadas en el frontend se generan automáticamente mediante **Laravel Wayfinder**.

## Comandos

### Desarrollo

```bash
composer run dev          # Levanta servidor PHP, queue worker y Vite en paralelo
```

O por separado:
```bash
php artisan serve
npm run dev
php artisan queue:listen --tries=1
```

### Build

```bash
npm run build             # Build de assets para producción
npm run build:ssr         # Build con SSR
```

### Setup inicial

```bash
composer run setup        # Instala dependencias, genera .env, migra base de datos y compila assets
```

### Lint y formato

```bash
composer run lint         # PHP: Pint (formatea)
composer run lint:check   # PHP: Pint (solo verifica)
npm run lint              # JS/TS: ESLint con autofix
npm run lint:check        # JS/TS: ESLint sin fix
npm run format            # Prettier en resources/
npm run format:check      # Prettier solo verificación
npm run types:check       # vue-tsc sin emitir
```

### Tests

```bash
composer run test         # Limpia config + lint PHP + PHPUnit
php artisan test          # Solo PHPUnit
php artisan test --filter NombreDelTest   # Un test específico
```

Los tests usan SQLite en memoria (`:memory:`), no PostgreSQL. La configuración está en `phpunit.xml`.

## Arquitectura

### Flujo de una request

1. Laravel resuelve la ruta (`routes/web.php`, `routes/settings.php`)
2. El controlador (o `Route::inertia()`) retorna una respuesta Inertia con un componente Vue y props
3. Inertia renderiza el componente Vue correspondiente desde `resources/js/pages/`
4. El layout se asigna automáticamente en `resources/js/app.ts` según el nombre del componente

### Selección de layout (`app.ts`)

| Nombre del componente | Layout aplicado |
|---|---|
| `Welcome` | Sin layout |
| `auth/*` | `AuthLayout` |
| `settings/*` | `AppLayout` + `SettingsLayout` (anidado) |
| Cualquier otro | `AppLayout` |

### Estructura PHP

- `app/Actions/` — lógica de negocio desacoplada de los controladores (patrón Action)
- `app/Http/Controllers/` — controladores delgados; los de auth/settings ya están scaffoldeados
- `app/Http/Requests/` — Form Requests para validación
- `app/Models/` — Eloquent models

### Estructura frontend (`resources/js/`)

- `pages/` — componentes de página Inertia (mapeados 1:1 con rutas)
- `layouts/` — layouts reutilizables (`AppLayout`, `AuthLayout`, `settings/Layout`)
- `components/` — componentes reutilizables; los de UI primitivos en `components/ui/`
- `composables/` — composables Vue (ej. `useAppearance` para tema claro/oscuro)
- `actions/` — funciones que wrappean llamadas Inertia (equivalente frontend de Actions)
- `lib/` — utilidades puras (ej. `flashToast.ts` para toasts desde el servidor)
- `types/` — tipos TypeScript compartidos
- `wayfinder/` — rutas PHP exportadas como funciones TS (generado automáticamente, no editar)

### Wayfinder

Las rutas se referencian desde el frontend usando funciones generadas en `resources/js/wayfinder/`. Estas se regeneran automáticamente con `npm run dev` o `npm run build`. Nunca escribir URLs hardcodeadas en el frontend.

### Flash toasts

El servidor puede enviar toasts al frontend usando el helper de flash. `initializeFlashToast()` en `app.ts` escucha el evento `flash` de Inertia y llama a `vue-sonner`.

### Tema claro/oscuro

Gestionado por `useAppearance` composable. La preferencia se persiste en `localStorage` y en una cookie (para SSR). Se inicializa en `app.ts` antes de montar la app para evitar FOUC.

## Variables de entorno clave

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigacup
DB_USERNAME=
DB_PASSWORD=
VITE_APP_NAME="${APP_NAME}"
```
