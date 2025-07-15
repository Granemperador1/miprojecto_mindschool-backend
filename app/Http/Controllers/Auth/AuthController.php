<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Ignorar cualquier valor de 'role' recibido y asignar siempre 'estudiante'
        // (ya está implementado: no se usa $request->role en ningún momento)
        // Solo para mayor claridad, eliminamos cualquier referencia a 'role' en la validación
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            ],
            // 'role' => 'prohibido', // No se valida ni se usa
        ], [
            'password.regex' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Verificar que el rol 'estudiante' existe antes de asignarlo
        $estudianteRole = Role::where('name', 'estudiante')->first();
        if (!$estudianteRole) {
            // Si no existe, crear el rol
            $estudianteRole = Role::create(['name' => 'estudiante']);
        }
        
        // Asignar rol por defecto (estudiante)
        $user->assignRole($estudianteRole);

        // Cargar roles para la respuesta
        $user->load('roles');
        $userArr = $user->toArray();
        $userArr['roles'] = $user->roles->pluck('name')->toArray();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $userArr,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        
        // Cargar los roles del usuario
        $user->load('roles');
        $userArr = $user->toArray();
        $userArr['roles'] = $user->roles->pluck('name')->toArray();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $userArr,
            'token' => $token
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada exitosamente'
        ]);
    }
} 