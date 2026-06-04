<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrador = 'administrador';
    case Coordinador = 'coordinador';
    case Docente = 'docente';
    case Autoridad = 'autoridad';
    case Estudiante = 'estudiante';

    public function label(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Coordinador => 'Coordinador',
            self::Docente => 'Docente',
            self::Autoridad => 'Autoridad',
            self::Estudiante => 'Estudiante',
        };
    }
}
