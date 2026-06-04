<?php

namespace App\SeguridadAcceso\Controllers;

use App\AdministracionSistema\Models\Gestion;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\SeguridadAcceso\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuariosController extends Controller
{
    /** Roles de personal que NO se generan por flujos automáticos (admisión / portal). */
    private const ROLES_AUTOMATICOS = ['docente', 'estudiante'];

    public function index(Request $request): Response
    {
        $query = User::query()
            ->when($request->filled('rol'), fn ($q) => $q->where('role', $request->rol))
            ->when($request->filled('estado'), fn ($q) => $q->where('activo', $request->estado === 'activo'))
            ->when($request->filled('gestion_id'), function ($q) use ($request) {
                $gid = (int) $request->gestion_id;
                $q->where(function ($inner) use ($gid) {
                    $inner->where(function ($sub) use ($gid) {
                        $sub->where('role', 'docente')
                            ->whereHas('docente', fn ($d) => $d->whereHas('grupos', fn ($g) => $g->where('gestion_id', $gid)));
                    })
                        ->orWhere(function ($sub) use ($gid) {
                            $sub->where('role', 'estudiante')
                                ->whereHas('candidatoEstudiante', fn ($ce) => $ce->whereHas('postulaciones', fn ($p) => $p->where('gestion_id', $gid)));
                        })
                        ->orWhereNotIn('role', ['docente', 'estudiante']);
                });
            });

        $resumen = [
            'activos' => (clone $query)->where('activo', true)->count(),
            'inactivos' => (clone $query)->where('activo', false)->count(),
        ];

        $currentUserId = $request->user()->id;

        $usuarios = $query
            ->with('rol')
            ->orderByDesc('activo')
            ->orderBy('name')
            ->paginate(30)
            ->through(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'username' => $u->username,
                'email' => $u->email,
                'role' => $u->role,
                'rol_label' => $u->rol?->label ?? $u->role,
                'activo' => $u->activo,
                'es_actual' => $u->id === $currentUserId,
            ]);

        $roles = Rol::orderByDesc('es_sistema')->orderBy('label')->get(['nombre', 'label']);
        $gestiones = Gestion::orderByDesc('anio')->orderByDesc('semestre')->get(['id', 'anio', 'semestre']);

        return Inertia::render('SeguridadAcceso/Usuarios/Index', [
            'usuarios' => $usuarios,
            'resumen' => $resumen,
            'roles' => $roles,
            'gestiones' => $gestiones,
            'filtros' => [
                'rol' => $request->rol ?? '',
                'estado' => $request->estado ?? '',
                'gestion_id' => $request->gestion_id ?? '',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', Rule::exists('rol', 'nombre')],
        ]);

        User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'activo' => true,
            'email_verified_at' => now(),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => "Usuario «{$data['name']}» creado."]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', 'string', Rule::exists('rol', 'nombre')],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== $user->role) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No puedes cambiar tu propio rol.']);
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return back()->with('flash', ['type' => 'success', 'message' => 'Usuario actualizado.']);
    }

    public function toggleActivo(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No puedes desactivar tu propia cuenta.']);
        }

        $user->update(['activo' => ! $user->activo]);

        $estado = $user->activo ? 'activado' : 'desactivado';

        return back()->with('flash', ['type' => 'success', 'message' => "Usuario {$estado}."]);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No puedes eliminar tu propia cuenta.']);
        }

        if (in_array($user->role, self::ROLES_AUTOMATICOS, true)) {
            return back()->with('flash', ['type' => 'error', 'message' => 'Los docentes y estudiantes se gestionan desde sus módulos correspondientes.']);
        }

        $user->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Usuario eliminado.']);
    }
}
