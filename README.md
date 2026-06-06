# SIGACUP

Sistema de Gestión Académica del Curso de Preparación Universitaria (CUP) — FICCT, UAGRM.

Desarrollado con **Laravel 13 + Inertia.js v3 + Vue 3 + TypeScript + Tailwind CSS v4 + PostgreSQL**.

---

## Requisitos

| Herramienta | Versión mínima |
|---|---|
| PHP | 8.3 |
| Composer | 2.x |
| Node.js | 20.x |
| npm | 10.x |
| PostgreSQL | 15+ |

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/CHLimber/SIGACUP.git
cd SIGACUP
```

### 2. Crear la base de datos PostgreSQL

```sql
CREATE DATABASE sigacup;
```

### 3. Configurar el archivo `.env`

```bash
cp .env.example .env
```

Editar `.env` y completar las credenciales de la base de datos:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigacup
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_contraseña
```

### 4. Ejecutar el setup automático

```bash
composer run setup
```

Este comando realiza en orden:

1. Instala dependencias PHP (`composer install`)
2. Copia `.env.example` → `.env` si aún no existe
3. Genera la clave de la aplicación (`APP_KEY`)
4. Ejecuta todas las migraciones
5. Puebla la base de datos con datos iniciales (`db:seed`)
6. Crea el enlace simbólico de almacenamiento (`storage:link`)
7. Instala dependencias JS (`npm install`)
8. Compila los assets para producción (`npm run build`)

---

## Desarrollo

```bash
composer run dev
```

Levanta en paralelo: servidor PHP, queue worker y servidor Vite (HMR).

---

## Credenciales de demo

Tras correr `composer run setup`, los siguientes usuarios quedan disponibles:

| Rol | Usuario | Email | Contraseña |
|---|---|---|---|
| Administrador | `admin` | admin@ficct.edu.bo | `Admin1234!` |
| Coordinador | `coordinador` | coordinador@ficct.edu.bo | `Coord1234!` |
| Docente | `docente` | docente@ficct.edu.bo | `Docente1234!` |
| Autoridad | `autoridad` | autoridad@ficct.edu.bo | `Autor1234!` |

La gestión activa es **2026-1** (estado: admisión). Hay datos de 3 gestiones anteriores para reportes y comparativas.

---

## Comandos útiles

```bash
# Linting y formato
composer run lint          # PHP: Pint (formatea)
npm run lint               # JS/TS: ESLint con autofix
npm run format             # Prettier en resources/
npm run types:check        # Verificación de tipos TypeScript

# Tests
composer run test          # Lint PHP + PHPUnit (SQLite en memoria)
php artisan test           # Solo PHPUnit

# Build de producción
npm run build
npm run build:ssr          # Build con SSR
```

---

## Variables de entorno opcionales

```env
# Pasarela de pago (Stripe)
STRIPE_PUBLISHABLE_KEY=
STRIPE_SECRET_KEY=

# Asistente IA para reportes (Anthropic Claude)
ANTHROPIC_API_KEY=
ANTHROPIC_MODEL=claude-haiku-4-5-20251001
```

Si no se configuran, las funcionalidades correspondientes quedan deshabilitadas sin afectar el resto del sistema.

---

## Estructura del proyecto

El código está organizado en **6 módulos** que reflejan la arquitectura del sistema:

| Módulo | Descripción |
|---|---|
| `SeguridadAcceso` | Usuarios, roles, permisos, bitácora de auditoría |
| `AdministracionSistema` | Gestiones académicas, parámetros, carreras, cupos |
| `RegistroInscripcion` | Portal de candidatos, postulaciones, pagos |
| `OrganizacionAcademica` | Grupos, aulas, horarios, docentes |
| `EvaluacionAdmision` | Admisión por mérito, calificaciones, evaluaciones |
| `ReportesNotificaciones` | Reportes, resúmenes estadísticos, notificaciones |
