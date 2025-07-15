<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntregaTarea extends Model
{
    use HasFactory;

    protected $table = 'entregas_tareas';
    protected $fillable = [
        'tarea_id',
        'estudiante_id',
        'archivo_url',
        'comentarios',
        'calificacion',
        'comentarios_profesor',
        'fecha_entrega',
        'estado'
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'calificacion' => 'decimal:2'
    ];

    public function tarea()
    {
        return $this->belongsTo(Tarea::class, 'tarea_id');
    }

    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }
} 