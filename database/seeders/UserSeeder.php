<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@mindschool.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('admin');

        // Crear usuario profesor
        $profesor = User::create([
            'name' => 'Profesor Ejemplo',
            'email' => 'profesor@mindschool.com',
            'password' => Hash::make('password123'),
        ]);
        $profesor->assignRole('profesor');

        // Crear usuario estudiante
        $estudiante = User::create([
            'name' => 'Estudiante Ejemplo',
            'email' => 'estudiante@mindschool.com',
            'password' => Hash::make('password123'),
        ]);
        $estudiante->assignRole('estudiante');

        // Crear algunos usuarios adicionales
        $usuarios = [
            [
                'name' => 'María García',
                'email' => 'maria@mindschool.com',
                'role' => 'estudiante'
            ],
            [
                'name' => 'Carlos López',
                'email' => 'carlos@mindschool.com',
                'role' => 'profesor'
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'ana@mindschool.com',
                'role' => 'estudiante'
            ],
            [
                'name' => 'Luis Rodríguez',
                'email' => 'luis@mindschool.com',
                'role' => 'profesor'
            ]
        ];

        foreach ($usuarios as $usuarioData) {
            $usuario = User::create([
                'name' => $usuarioData['name'],
                'email' => $usuarioData['email'],
                'password' => Hash::make('password123'),
            ]);
            $usuario->assignRole($usuarioData['role']);
        }
    }
} 