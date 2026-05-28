<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $users = [
            [
                'name'     => 'Administrador FICCT',
                'username' => 'admin',
                'email'    => 'admin@ficct.edu.bo',
                'password' => 'Admin1234!',
                'role'     => UserRole::Administrador,
            ],
            [
                'name'     => 'Coordinador CUP',
                'username' => 'coordinador',
                'email'    => 'coordinador@ficct.edu.bo',
                'password' => 'Coord1234!',
                'role'     => UserRole::Coordinador,
            ],
            [
                'name'     => 'Docente Ejemplo',
                'username' => 'docente',
                'email'    => 'docente@ficct.edu.bo',
                'password' => 'Docente1234!',
                'role'     => UserRole::Docente,
            ],
            [
                'name'     => 'Autoridad FICCT',
                'username' => 'autoridad',
                'email'    => 'autoridad@ficct.edu.bo',
                'password' => 'Autor1234!',
                'role'     => UserRole::Autoridad,
            ],
        ];

        foreach ($users as $data) {
            User::firstOrCreate(
                ['username' => $data['username']],
                [
                    'name'              => $data['name'],
                    'email'             => $data['email'],
                    'password'          => $data['password'],
                    'role'              => $data['role'],
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
