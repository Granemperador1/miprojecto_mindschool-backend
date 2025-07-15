<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMensajeRequest;
use App\Models\Mensaje;
use Illuminate\Http\Request;

class MensajeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Mensajes enviados o recibidos por el usuario autenticado
        $userId = $request->user()->id;
        $mensajes = Mensaje::where('remitente_id', $userId)
            ->orWhere('destinatario_id', $userId)
            ->orderBy('fecha_envio', 'desc')
            ->get();
        return response()->json(['data' => $mensajes]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMensajeRequest $request)
    {
        $validatedData = $request->validated();

        $mensaje = Mensaje::create([
            'remitente_id' => $request->user()->id, // o auth()->id()
            ...$validatedData,
        ]);

        return response()->json(['data' => $mensaje], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Mensaje $mensaje)
    {
        // Solo permitir ver si es el remitente o destinatario
        if ($request->user()->id !== $mensaje->remitente_id && $request->user()->id !== $mensaje->destinatario_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        return response()->json(['data' => $mensaje]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mensaje $mensaje)
    {
        // Solo permitir editar si es el remitente
        if ($request->user()->id !== $mensaje->remitente_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        
        $request->validate([
            'asunto' => 'string|max:255',
            'contenido' => 'string',
            'tipo' => 'in:consulta,respuesta,notificacion,general',
            'estado' => 'in:enviado,leido,archivado'
        ]);
        
        $mensaje->update($request->all());
        return response()->json(['data' => $mensaje]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mensaje $mensaje)
    {
        // Solo permitir borrar si es el remitente
        if (auth()->id() !== $mensaje->remitente_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $mensaje->delete();
        return response()->json(['message' => 'Mensaje eliminado exitosamente']);
    }

    public function enviados(Request $request)
    {
        $userId = $request->user()->id;
        $mensajes = Mensaje::where('remitente_id', $userId)
            ->orderBy('fecha_envio', 'desc')
            ->get();
        
        return response()->json(['data' => $mensajes]);
    }

    public function recibidos(Request $request)
    {
        $userId = $request->user()->id;
        $mensajes = Mensaje::where('destinatario_id', $userId)
            ->orderBy('fecha_envio', 'desc')
            ->get();
        
        return response()->json(['data' => $mensajes]);
    }

    public function marcarComoLeido(Mensaje $mensaje)
    {
        // Solo permitir marcar como leído si es el destinatario
        if (auth()->id() !== $mensaje->destinatario_id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }
        $mensaje->update([
            'estado' => 'leido',
            'fecha_lectura' => now()
        ]);
        $mensaje->refresh();
        $data = $mensaje->toArray();
        $data['fecha_lectura'] = $mensaje->fecha_lectura ? $mensaje->fecha_lectura->toDateString() : null;
        return response()->json(['data' => $data]);
    }
}
