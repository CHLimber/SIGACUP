<?php

namespace App\SeguridadAcceso\Actions;

use App\Mail\UsuarioCredencialesGeneradas;
use App\Models\User;
use App\SeguridadAcceso\Models\Rol;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Importa cuentas de personal (no docentes/estudiantes) desde un CSV.
 *
 * Cabeceras esperadas: name, username, email, role, password
 * - `username` y `password` son opcionales: se autogeneran si faltan.
 * - Los roles `docente` y `estudiante` se rechazan (vienen de flujos automáticos).
 */
class ImportarUsuariosCsv
{
    /** Roles que NO pueden crearse por carga masiva (se generan por flujos). */
    private const ROLES_BLOQUEADOS = ['docente'];

    private const CABECERAS = ['name', 'username', 'email', 'role', 'password'];

    /**
     * Parsea y valida el archivo, devolviendo filas válidas y errores por fila.
     * No persiste nada.
     *
     * @return array{validos: array<int, array<string, mixed>>, errores: array<int, array{linea:int, datos:array, errores:array<int, string>}>}
     */
    public function previsualizar(UploadedFile $archivo): array
    {
        $filas = $this->leerCsv($archivo);

        $rolesValidos = Rol::whereNotIn('nombre', self::ROLES_BLOQUEADOS)->pluck('nombre')->all();
        $usernamesBd = User::pluck('username')->map(fn ($u) => strtolower((string) $u))->all();
        $emailsBd = User::pluck('email')->map(fn ($e) => strtolower((string) $e))->all();

        $validos = [];
        $errores = [];
        $usernamesEnLote = [];
        $emailsEnLote = [];

        foreach ($filas as $i => $fila) {
            $linea = $i + 2; // +1 cabecera, +1 índice base 1
            $errs = [];

            $name = trim((string) ($fila['name'] ?? ''));
            $email = strtolower(trim((string) ($fila['email'] ?? '')));
            $role = strtolower(trim((string) ($fila['role'] ?? '')));
            $username = strtolower(trim((string) ($fila['username'] ?? '')));
            $password = (string) ($fila['password'] ?? '');

            if ($name === '') {
                $errs[] = 'El nombre es obligatorio.';
            }

            if ($email === '') {
                $errs[] = 'El correo es obligatorio.';
            } elseif (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errs[] = 'El correo no es válido.';
            } elseif (in_array($email, $emailsBd, true) || in_array($email, $emailsEnLote, true)) {
                $errs[] = 'El correo ya existe.';
            }

            if ($role === '') {
                $errs[] = 'El rol es obligatorio.';
            } elseif (in_array($role, self::ROLES_BLOQUEADOS, true)) {
                $errs[] = "El rol «{$role}» no se permite por carga masiva.";
            } elseif (! in_array($role, $rolesValidos, true)) {
                $errs[] = "El rol «{$role}» no existe.";
            }

            // Username: autogenerar si falta.
            if ($username === '' && $name !== '') {
                $username = $this->generarUsername($name, $usernamesBd, $usernamesEnLote);
            } elseif ($username !== '') {
                if (in_array($username, $usernamesBd, true) || in_array($username, $usernamesEnLote, true)) {
                    $errs[] = "El usuario «{$username}» ya existe.";
                } elseif (! preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
                    $errs[] = 'El usuario solo admite letras, números, ., - y _.';
                }
            }

            // Password: autogenerar si falta; validar formato si viene.
            $passwordGenerada = false;
            if ($password === '') {
                $password = $this->generarPassword();
                $passwordGenerada = true;
            } elseif (! $this->passwordValida($password)) {
                $errs[] = 'La contraseña debe tener mínimo 8 caracteres con mayúscula, minúscula y número.';
            }

            $registro = [
                'linea' => $linea,
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'password' => $password,
                'password_generada' => $passwordGenerada,
            ];

            if (empty($errs)) {
                $usernamesEnLote[] = $username;
                $emailsEnLote[] = $email;
                $validos[] = $registro;
            } else {
                $errores[] = ['linea' => $linea, 'datos' => $registro, 'errores' => $errs];
            }
        }

        return ['validos' => $validos, 'errores' => $errores];
    }

    /**
     * Crea los usuarios válidos. Devuelve la cantidad creada.
     *
     * @param  array<int, array<string, mixed>>  $filas  filas ya validadas por previsualizar()
     */
    public function importar(array $filas, bool $enviarCorreo = false): int
    {
        $creados = 0;

        DB::transaction(function () use ($filas, $enviarCorreo, &$creados) {
            foreach ($filas as $fila) {
                // Revalidación mínima de unicidad en caso de cambios concurrentes.
                if (User::where('username', $fila['username'])->orWhere('email', $fila['email'])->exists()) {
                    continue;
                }

                $passwordPlano = (string) $fila['password'];

                User::create([
                    'name' => $fila['name'],
                    'username' => $fila['username'],
                    'email' => $fila['email'],
                    'password' => $passwordPlano,
                    'role' => $fila['role'],
                    'activo' => true,
                    'email_verified_at' => now(),
                ]);

                if ($enviarCorreo) {
                    Mail::to($fila['email'])->send(
                        new UsuarioCredencialesGeneradas($fila['name'], $fila['username'], $passwordPlano),
                    );
                }

                $creados++;
            }
        });

        return $creados;
    }

    /** Contenido de la plantilla CSV descargable. */
    public function plantilla(): string
    {
        $lineas = [
            implode(',', self::CABECERAS),
            'Juan Pérez,jperez,juan.perez@ficct.edu.bo,coordinador,',
            'Ana Gómez,,ana.gomez@ficct.edu.bo,autoridad,Clave2026!',
        ];

        return "\xEF\xBB\xBF".implode("\r\n", $lineas)."\r\n";
    }

    /**
     * Lee el CSV mapeando cada fila a un array asociativo por cabecera.
     *
     * @return array<int, array<string, string>>
     */
    private function leerCsv(UploadedFile $archivo): array
    {
        $handle = fopen($archivo->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $filas = [];
        $cabeceras = null;

        while (($datos = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($datos === [null] || $datos === false) {
                continue;
            }

            if ($cabeceras === null) {
                // Normaliza cabeceras (quita BOM y espacios, a minúsculas).
                $cabeceras = array_map(
                    fn ($c) => strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $c))),
                    $datos,
                );

                continue;
            }

            $fila = [];
            foreach ($cabeceras as $idx => $clave) {
                $fila[$clave] = $datos[$idx] ?? '';
            }

            // Ignora filas totalmente vacías.
            if (trim(implode('', $fila)) === '') {
                continue;
            }

            $filas[] = $fila;
        }

        fclose($handle);

        return $filas;
    }

    /** Genera un username único a partir del nombre. */
    private function generarUsername(string $name, array $bd, array $lote): string
    {
        $base = Str::of($name)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '.')->trim('.')->toString();
        $base = $base !== '' ? $base : 'usuario';
        $candidato = $base;
        $n = 1;

        while (in_array($candidato, $bd, true) || in_array($candidato, $lote, true)) {
            $candidato = $base.$n;
            $n++;
        }

        return $candidato;
    }

    /** Genera una contraseña que cumple la política (mayúscula+minúscula+número). */
    private function generarPassword(): string
    {
        return 'Cup'.Str::upper(Str::random(2)).Str::lower(Str::random(3)).random_int(1000, 9999);
    }

    private function passwordValida(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }
}
