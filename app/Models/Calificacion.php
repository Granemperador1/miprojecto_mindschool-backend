<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calificacion extends Model
{
    use HasFactory;

    protected $table = 'calificaciones';

    protected $fillable = [
        'estudiante_id',
        'curso_id',
        'leccion_id',
        'tipo_evaluacion',
        'calificacion',
        'peso',
        'comentarios',
        'fecha_evaluacion',
        'evaluador_id',
        'estado'
    ];

    protected $casts = [
        'calificacion' => 'decimal:2',
        'peso' => 'decimal:2',
        'fecha_evaluacion' => 'datetime',
    ];

    // Relaciones
    public function estudiante()
    {
        return $this->belongsTo(User::class, 'estudiante_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function leccion()
    {
        return $this->belongsTo(Leccion::class, 'leccion_id');
    }

    public function evaluador()
    {
        return $this->belongsTo(User::class, 'evaluador_id');
    }

    // Scopes
    public function scopePorEstudiante($query, $estudianteId)
    {
        return $query->where('estudiante_id', $estudianteId);
    }

    public function scopePorCurso($query, $cursoId)
    {
        return $query->where('curso_id', $cursoId);
    }

    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo_evaluacion', $tipo);
    }

    public function scopePublicadas($query)
    {
        return $query->where('estado', 'publicada');
    }

    // Métodos
    public function getCalificacionFinalAttribute()
    {
        return $this->calificacion * $this->peso;
    }

    public function getPromedioCurso($cursoId, $estudianteId)
    {
        return $this->where('curso_id', $cursoId)
                    ->where('estudiante_id', $estudianteId)
                    ->where('estado', 'publicada')
                    ->avg('calificacion');
    }
}
