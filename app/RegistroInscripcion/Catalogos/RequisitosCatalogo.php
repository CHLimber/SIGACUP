<?php

namespace App\RegistroInscripcion\Catalogos;

use App\OrganizacionAcademica\Models\CandidatoDocente;
use App\RegistroInscripcion\Models\CandidatoEstudiante;

class RequisitosCatalogo
{
    public const MIMES_DOCUMENTO = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];

    public const MIMES_FOTO = ['image/jpeg', 'image/png', 'image/webp'];

    public const TAMANO_MAX_KB = 4096;

    public const ESTUDIANTE = [
        'fotocopia_ci' => [
            'nombre' => 'Fotocopia de CI',
            'descripcion' => 'Fotocopia o escaneo legible de ambas caras de tu Cédula de Identidad en un solo archivo.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'certificado_nacimiento' => [
            'nombre' => 'Certificado de nacimiento',
            'descripcion' => 'Certificado vigente del SERECI (PDF o imagen clara).',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'titulo_bachiller' => [
            'nombre' => 'Título de Bachiller',
            'descripcion' => 'Diploma de bachiller emitido por el colegio o unidad educativa.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'certificado_colegio' => [
            'nombre' => 'Certificado del colegio',
            'descripcion' => 'Certificado de estudios o constancia emitida por la unidad educativa.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'libreta_ultimo_anio' => [
            'nombre' => 'Libreta del último año',
            'descripcion' => 'Libreta de calificaciones del último año de secundaria (6.º de secundaria).',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'foto_carnet' => [
            'nombre' => 'Foto carnet 4x4',
            'descripcion' => 'Fondo rojo, sin lentes ni gorra. Solo formato imagen.',
            'mimes' => self::MIMES_FOTO,
            'obligatorio' => true,
        ],
    ];

    public const DOCENTE = [
        'fotocopia_ci' => [
            'nombre' => 'Fotocopia de CI',
            'descripcion' => 'Fotocopia o escaneo legible de ambas caras de tu Cédula de Identidad en un solo archivo.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'cv' => [
            'nombre' => 'Currículum Vitae',
            'descripcion' => 'CV en PDF actualizado con tu trayectoria académica y profesional.',
            'mimes' => ['application/pdf'],
            'obligatorio' => true,
        ],
        'titulo_profesional' => [
            'nombre' => 'Título profesional',
            'descripcion' => 'Diploma académico (Licenciatura, Ingeniería o equivalente).',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'diploma_academico' => [
            'nombre' => 'Diploma académico',
            'descripcion' => 'Diploma de licenciatura o equivalente emitido por la institución.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => true,
        ],
        'certificado_maestria' => [
            'nombre' => 'Certificado de maestría (opcional)',
            'descripcion' => 'Certificado o diploma de maestría, si posees este grado académico.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => false,
        ],
        'certificado_diplomado' => [
            'nombre' => 'Certificado de diplomado (opcional)',
            'descripcion' => 'Certificado de diplomado, si posees esta certificación.',
            'mimes' => self::MIMES_DOCUMENTO,
            'obligatorio' => false,
        ],
    ];

    public static function paraCandidato(object $candidato): array
    {
        return match (true) {
            $candidato instanceof CandidatoEstudiante => self::ESTUDIANTE,
            $candidato instanceof CandidatoDocente => self::DOCENTE,
            default => [],
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
