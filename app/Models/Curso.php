<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';
    protected $fillable = ['titulo', 'descripcion', 'duracion', 'nivel', 'precio', 'estado', 'instructor_id', 'imagen_url', 'tipo', 'codigo_invitacion'];

    /**
     * Relación con el instructor del curso
     */
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * Relación con las inscripciones del curso
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'curso_id');
    }

    /**
     * Relación con las tareas del curso
     */
    public function tareas()
    {
        return $this->hasMany(Tarea::class, 'curso_id');
    }

    /**
     * Relación con las lecciones del curso
     */
    public function lecciones()
    {
        return $this->hasMany(Leccion::class, 'curso_id');
    }

    /**
     * Relación muchos a muchos con usuarios (alumnos) a través de curso_usuario
     */
    public function alumnos()
    {
        return $this->belongsToMany(User::class, 'curso_usuario', 'curso_id', 'usuario_id')
            ->withPivot('tipo_acceso', 'fecha_acceso')
            ->withTimestamps();
    }

    /**
     * Relación con la tabla pivote curso_usuario
     */
    public function cursoUsuarios()
    {
        return $this->hasMany(CursoUsuario::class, 'curso_id');
    }
}
