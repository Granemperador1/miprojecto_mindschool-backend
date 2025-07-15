<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';
    protected $fillable = ['user_id', 'curso_id', 'estado', 'fecha_inscripcion', 'progreso'];
    
    protected $casts = [
        'fecha_inscripcion' => 'datetime',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($inscripcion) {
            if (empty($inscripcion->fecha_inscripcion)) {
                $inscripcion->fecha_inscripcion = now();
            }
        });
    }

    public function alumno()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }
}
