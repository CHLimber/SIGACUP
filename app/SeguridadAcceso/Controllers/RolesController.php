<?php

namespace App\SeguridadAcceso\Controllers;

use App\Http\Controllers\Controller;
use App\SeguridadAcceso\Models\Permiso;
use App\SeguridadAcceso\Models\Rol;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RolesController extends Controller
{
    // CU21 — Gestionar roles y permisos (listar roles y permisos)
    public function index(): Response
    {
        $roles = Rol::with('permisos:id')
            ->withCount('usuarios')
            ->orderByDesc('es_sistema')
            ->orderBy('label')
            ->get()
            ->map(fn (Rol $r) => [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'label' => $r->label,
                'descripcion' => $r->descripcion,
                'es_sistema' => $r->es_sistema,
                'usuarios_count' => $r->usuarios_count,
                'permisos' => $r->permisos->pluck('id'),
            ]);

        $permisos = Permiso::orderBy('grupo')->orderBy('label')->get()
            ->groupBy('grupo')
            ->map(fn ($items, $grupo) => [
                'grupo' => $grupo,
                'permisos' => $items->map(fn (Permiso $p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'label' => $p->label,
                ])->values(),
            ])
            ->values();

        return Inertia::render('SeguridadAcceso/Roles/Index', [
            'roles' => $roles,
            'permisos' => $permisos,
        ]);
    }

    // CU21 — Gestionar roles y permisos (crear rol)
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'exists:permiso,id'],
        ]);

        $nombre = $this->slugUnico($data['label']);

        $rol = Rol::create([
            'nombre' => $nombre,
            'label' => $data['label'],
            'descripcion' => $data['descripcion'] ?? null,
            'es_sistema' => false,
        ]);

        $rol->permisos()->sync($data['permisos'] ?? []);

        return back()->with('flash', ['type' => 'success', 'message' => "Rol «{$rol->label}» creado."]);
    }

    // CU21 — Gestionar roles y permisos (editar rol y sincronizar permisos)
    public function update(Request $request, Rol $rol): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'permisos' => ['array'],
            'permisos.*' => ['integer', 'exists:permiso,id'],
        ]);

        $rol->update([
            'label' => $data['label'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        $rol->permisos()->sync($data['permisos'] ?? []);

        return back()->with('flash', ['type' => 'success', 'message' => "Rol «{$rol->label}» actualizado."]);
    }

    // CU21 — Gestionar roles y permisos (eliminar rol)
    public function destroy(Rol $rol): RedirectResponse
    {
        if ($rol->es_sistema) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No se puede eliminar un rol del sistema.']);
        }

        if ($rol->usuarios()->exists()) {
            return back()->with('flash', ['type' => 'error', 'message' => 'No se puede eliminar un rol con usuarios asignados. Reasígnalos primero.']);
        }

        $label = $rol->label;
        $rol->delete();

        return back()->with('flash', ['type' => 'success', 'message' => "Rol «{$label}» eliminado."]);
    }

    private function slugUnico(string $label): string
    {
        $base = Str::slug($label, '_') ?: 'rol';
        $nombre = $base;
        $i = 2;

        while (Rol::where('nombre', $nombre)->exists()) {
            $nombre = $base.'_'.$i++;
        }

        return $nombre;
    }
}
