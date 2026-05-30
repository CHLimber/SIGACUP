<?php

namespace App\RegistroPublico\Requests;

use App\Models\Persona;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarCandidatoEstudianteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ci'               => ['required', 'string', 'max:20', Rule::unique(Persona::class)],
            'apellido'         => ['required', 'string', 'max:255'],
            'nombres'          => ['required', 'string', 'max:255'],
            'fecha_nacimiento' => ['required', 'date', 'before:today'],
            'sexo'             => ['required', 'string', Rule::in(['masculino', 'femenino'])],
            'telefono'         => ['required', 'string', 'max:30'],
            'email'            => ['required', 'string', 'email', 'max:255', Rule::unique(Persona::class)],
            'direccion'        => ['required', 'string', 'max:500'],
            'carrera1_id'      => ['required', 'integer', 'exists:carrera,id'],
            'carrera2_id'      => ['required', 'integer', 'exists:carrera,id', 'different:carrera1_id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ci'          => 'carnet de identidad',
            'apellido'    => 'apellido',
            'nombres'     => 'nombres',
            'fecha_nacimiento' => 'fecha de nacimiento',
            'sexo'        => 'sexo',
            'telefono'    => 'teléfono',
            'email'       => 'correo electrónico',
            'direccion'   => 'dirección',
            'carrera1_id' => 'primera opción de carrera',
            'carrera2_id' => 'segunda opción de carrera',
        ];
    }
}
