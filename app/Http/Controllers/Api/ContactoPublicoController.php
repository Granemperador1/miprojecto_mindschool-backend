<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactoPublicoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'telefono' => 'nullable|string|max:20',
            'asunto' => 'nullable|string|max:255',
            'mensaje' => 'required|string|max:2000',
        ]);

        $contacto = Contacto::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'telefono' => $request->telefono,
            'asunto' => $request->asunto,
            'mensaje' => $request->mensaje,
        ]);

        // Enviar email de notificación
        $destino = 'info@mindschool.com';
        Mail::raw(
            "Nuevo mensaje de contacto:\n" .
            "Nombre: {$contacto->nombre}\n" .
            "Email: {$contacto->email}\n" .
            "Teléfono: {$contacto->telefono}\n" .
            "Asunto: {$contacto->asunto}\n" .
            "Mensaje: {$contacto->mensaje}",
            function ($message) use ($destino) {
                $message->to($destino)
                        ->subject('Nuevo mensaje de contacto MindSchool');
            }
        );

        return response()->json(['message' => 'Mensaje enviado correctamente. ¡Gracias por contactarnos!'], 201);
    }
} 