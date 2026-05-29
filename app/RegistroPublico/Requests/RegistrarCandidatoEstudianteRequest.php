<?php

namespace App\RegistroPublico\Requests;

use App\GestionEstudiantes\Models\CandidatoEstudiante;
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
        $carreras = ['sistemas', 'informatica', 'redes', 'robotica'];

        return [
            'ci'                     => ['required', 'string', 'max:20', Rule::unique(CandidatoEstudiante::class)],
            'apellido'               => ['required', 'string', 'max:255'],
            'nombres'                => ['required', 'string', 'max:255'],
            'fecha_nacimiento'       => ['required', 'date', 'before:today'],
            'sexo'                   => ['required', 'string', Rule::in(['masculino', 'femenino'])],
            'telefono'               => ['required', 'string', 'max:30'],
            'email'                  => ['required', 'string', 'email', 'max:255', Rule::unique(CandidatoEstudiante::class)],
            'direccion'              => ['required', 'string', 'max:500'],
            'carrera_primera_opcion' => ['required', 'string', Rule::in($carreras)],
            'carrera_segunda_opcion' => ['required', 'string', Rule::in($carreras), 'different:carrera_primera_opcion'],
        ];
    }

    public function attributes(): array
    {
        return [
            'ci'                     => 'carnet de identidad',
            'apellido'               => 'apellido',
            'nombres'                => 'nombres',
            'fecha_nacimiento'       => 'fecha de nacimiento',
            'sexo'                   => 'sexo',
            'telefono'               => 'teléfono',
            'email'                  => 'correo electrónico',
            'direccion'              => 'dirección',
            'carrera_primera_opcion' => 'primera opción de carrera',
            'carrera_segunda_opcion' => 'segunda opción de carrera',
        ];
    }
}
