<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Multimedia;
use Illuminate\Http\Request;

class MultimediaController extends Controller
{
    public function index()
    {
        $multimedia = Multimedia::all();
        return response()->json(['data' => $multimedia]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'tipo' => 'required|in:video,audio,documento,imagen',
            'url' => 'required',
            'leccion_id' => 'required|exists:lecciones,id',
            'orden' => 'required|integer|min:1',
            'estado' => 'required|in:activo,inactivo'
        ]);

        $data = $request->all();
        
        // Si se envía un archivo, validar el tipo
        if ($request->hasFile('url')) {
            $file = $request->file('url');
            $extension = strtolower($file->getClientOriginalExtension());
            // Validar tamaño máximo (1GB)
            if ($file->getSize() > 1048576 * 1024) {
                return response()->json([
                    'message' => 'El archivo excede el tamaño máximo permitido (1GB)',
                    'errors' => [
                        'url' => ['El archivo excede el tamaño máximo permitido (1GB)']
                    ]
                ], 422);
            }
            // Validar extensiones permitidas según el tipo
            $allowedExtensions = [
                'video' => ['mp4', 'avi', 'mov', 'wmv'],
                'audio' => ['mp3', 'wav', 'ogg', 'aac'],
                'documento' => ['pdf', 'doc', 'docx', 'txt'],
                'imagen' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
            ];
            
            if (!in_array($extension, $allowedExtensions[$request->tipo] ?? [])) {
                return response()->json([
                    'message' => 'Tipo de archivo no permitido para este tipo de multimedia',
                    'errors' => [
                        'url' => ['Tipo de archivo no permitido para este tipo de multimedia']
                    ]
                ], 422);
            }
            
            $data['url'] = 'uploads/' . $file->getClientOriginalName();
        }

        $multimedia = Multimedia::create($data);
        return response()->json(['data' => $multimedia], 201);
    }

    public function show(Multimedia $multimedia)
    {
        return response()->json(['data' => $multimedia]);
    }

    public function update(Request $request, Multimedia $multimedia)
    {
        $request->validate([
            'titulo' => 'string|max:255',
            'descripcion' => 'string',
            'tipo' => 'in:video,audio,documento,imagen',
            'url' => 'string',
            'leccion_id' => 'exists:lecciones,id',
            'orden' => 'integer|min:1',
            'estado' => 'in:activo,inactivo'
        ]);

        $multimedia->update($request->all());
        return response()->json(['data' => $multimedia]);
    }

    public function destroy(Multimedia $multimedia)
    {
        $multimedia->delete();
        return response()->json(['message' => 'Multimedia eliminado exitosamente']);
    }

    public function leccionMultimedia($leccionId)
    {
        $multimedia = Multimedia::where('leccion_id', $leccionId)
            ->orderBy('orden')
            ->get();
        
        return response()->json(['data' => $multimedia]);
    }

    public function cursoMultimedia($cursoId)
    {
        $multimedia = Multimedia::whereHas('leccion', function($query) use ($cursoId) {
            $query->where('curso_id', $cursoId);
        })->orderBy('orden')->get();
        
        return response()->json(['data' => $multimedia]);
    }
} 