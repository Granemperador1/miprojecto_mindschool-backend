<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarea extends Model
{
    use HasFactory;

    protected $table = 'tareas';
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_asignacion',
        'fecha_entrega',
        'tipo',
        'archivo_url',
        'curso_id',
        'leccion_id',
        'estado',
        'puntos_maximos'
    ];

    protected $casts = [
        'fecha_asignacion' => 'datetime',
        'fecha_entrega' => 'datetime',
        'puntos_maximos' => 'integer'
    ];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function leccion()
    {
        return $this->belongsTo(Leccion::class);
    }

    public function entregas()
    {
        return $this->hasMany(EntregaTarea::class, 'tarea_id');
    }
} 