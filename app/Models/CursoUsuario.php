<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CursoUsuario extends Model
{
    protected $table = 'curso_usuario';
    protected $fillable = ['curso_id', 'usuario_id', 'tipo_acceso', 'fecha_acceso'];

    public function curso()
    {
        return $this->belongsTo(Curso::class, 'curso_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
} 