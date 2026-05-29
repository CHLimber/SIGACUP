<?php

namespace App\RegistroPublico\Requests;

use App\GestionDocentes\Models\CandidatoDocente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarCandidatoDocenteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci'               => ['required', 'string', 'max:20', Rule::unique(CandidatoDocente::class)],
            'apellido'         => ['required', 'string', 'max:255'],
            'nombres'          => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo'             => ['required', 'string', Rule::in(['masculino', 'femenino'])],
            'telefono'         => ['required', 'string', 'max:30'],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique(CandidatoDocente::class)],
            'direccion'        => ['required', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ci'               => 'carnet de identidad',
            'apellido'         => 'apellido',
            'nombres'          => 'nombres',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'sexo'             => 'sexo',
            'telefono'         => 'teléfono',
            'email'            => 'correo electrónico',
            'direccion'        => 'dirección',
        ];
    }
}
