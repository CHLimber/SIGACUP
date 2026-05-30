<?php

namespace App\AdministracionSistema\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateGestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fecha_inicio_inscripcion' => ['required', 'date'],
            'fecha_fin_inscripcion'    => ['required', 'date', 'after_or_equal:fecha_inicio_inscripcion'],
            'fecha_inicio_cursado'     => ['required', 'date'],
            'fecha_fin_cursado'        => ['required', 'date', 'after_or_equal:fecha_inicio_cursado'],
            'monto_matricula_bs'        => ['required', 'numeric', 'min:0'],
            'monto_matricula_usd'       => ['required', 'numeric', 'min:0'],
            'capacidad_max_grupo'       => ['required', 'integer', 'min:1', 'max:500'],
            'peso_examen_1'            => ['required', 'integer', 'min:0', 'max:100'],
            'peso_examen_2'            => ['required', 'integer', 'min:0', 'max:100'],
            'peso_examen_3'            => ['required', 'integer', 'min:0', 'max:100'],
            'nota_minima_aprobacion'   => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $suma = (int) $this->input('peso_examen_1')
                      + (int) $this->input('peso_examen_2')
                      + (int) $this->input('peso_examen_3');

                if ($suma !== 100) {
                    $validator->errors()->add(
                        'peso_examen_1',
                        "Los pesos de los tres exámenes deben sumar exactamente 100 (suma actual: {$suma})."
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_inicio_inscripcion.required'    => 'La fecha de inicio de inscripción es obligatoria.',
            'fecha_fin_inscripcion.required'       => 'La fecha de fin de inscripción es obligatoria.',
            'fecha_fin_inscripcion.after_or_equal' => 'La fecha fin debe ser igual o posterior al inicio de inscripción.',
            'fecha_inicio_cursado.required'        => 'La fecha de inicio de cursado es obligatoria.',
            'fecha_fin_cursado.required'           => 'La fecha de fin de cursado es obligatoria.',
            'fecha_fin_cursado.after_or_equal'     => 'La fecha fin debe ser igual o posterior al inicio de cursado.',
            'capacidad_max_grupo.required'         => 'La capacidad máxima es obligatoria.',
            'capacidad_max_grupo.min'              => 'La capacidad debe ser al menos 1.',
            'capacidad_max_grupo.max'              => 'La capacidad no puede superar 500.',
            'peso_examen_1.required'               => 'El peso del 1er examen es obligatorio.',
            'peso_examen_2.required'               => 'El peso del 2do examen es obligatorio.',
            'peso_examen_3.required'               => 'El peso del examen final es obligatorio.',
            'nota_minima_aprobacion.required'      => 'La nota mínima es obligatoria.',
            'nota_minima_aprobacion.min'           => 'La nota mínima debe ser al menos 1.',
            'nota_minima_aprobacion.max'           => 'La nota mínima no puede superar 100.',
        ];
    }
}
