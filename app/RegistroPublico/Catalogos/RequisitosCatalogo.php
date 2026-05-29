<?php

namespace App\RegistroPublico\Catalogos;

use App\GestionDocentes\Models\CandidatoDocente;
use App\GestionEstudiantes\Models\CandidatoEstudiante;

class RequisitosCatalogo
{
    public const MIMES_DOCUMENTO = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public const MIMES_FOTO = ['image/jpeg', 'image/png', 'image/webp'];

    public const TAMANO_MAX_KB = 4096;

    public const ESTUDIANTE = [
        'ci_anverso' => [
            'nombre'      => 'CI — anverso',
            'descripcion' => 'Foto o escaneo del anverso de tu Cédula de Identidad. Debe ser legible.',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'ci_reverso' => [
            'nombre'      => 'CI — reverso',
            'descripcion' => 'Foto o escaneo del reverso de tu Cédula de Identidad.',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'certificado_nacimiento' => [
            'nombre'      => 'Certificado de nacimiento',
            'descripcion' => 'Certificado vigente del SERECI (PDF o imagen clara).',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'diploma_bachiller' => [
            'nombre'      => 'Diploma de Bachiller o Libreta de 6.º de secundaria',
            'descripcion' => 'Si aún no tienes el diploma, sube tu libreta del último año aprobado.',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'foto_carnet' => [
            'nombre'      => 'Foto carnet 4x4',
            'descripcion' => 'Fondo rojo, sin lentes ni gorra. Solo formato imagen.',
            'mimes'       => self::MIMES_FOTO,
            'obligatorio' => true,
        ],
    ];

    public const DOCENTE = [
        'ci_anverso' => [
            'nombre'      => 'CI — anverso',
            'descripcion' => 'Foto o escaneo del anverso de tu Cédula de Identidad.',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'ci_reverso' => [
            'nombre'      => 'CI — reverso',
            'descripcion' => 'Foto o escaneo del reverso de tu Cédula de Identidad.',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'cv' => [
            'nombre'      => 'Currículum Vitae',
            'descripcion' => 'CV en PDF actualizado con tu trayectoria académica y profesional.',
            'mimes'       => ['application/pdf'],
            'obligatorio' => true,
        ],
        'titulo_profesional' => [
            'nombre'      => 'Título profesional',
            'descripcion' => 'Diploma académico (Licenciatura, Ingeniería o equivalente).',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'certificados_academicos' => [
            'nombre'      => 'Certificados académicos (opcional)',
            'descripcion' => 'Cursos, postgrados, especializaciones o certificaciones (un solo archivo, puedes unirlos en PDF).',
            'mimes'       => self::MIMES_DOCUMENTO,
            'obligatorio' => false,
        ],
    ];

    public static function paraCandidato(object $candidato): array
    {
        return match (true) {
            $candidato instanceof CandidatoEstudiante => self::ESTUDIANTE,
            $candidato instanceof CandidatoDocente    => self::DOCENTE,
            default                                   => [],
        };
    }

    public static function definicion(object $candidato, string $codigo): ?array
    {
        return self::paraCandidato($candidato)[$codigo] ?? null;
    }

    public static function codigosObligatorios(object $candidato): array
    {
        return array_keys(array_filter(
            self::paraCandidato($candidato),
            fn (array $def) => $def['obligatorio'],
        ));
    }
}
