<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leccion extends Model
{
    use HasFactory;

    protected $table = 'lecciones';
    protected $fillable = ['titulo', 'descripcion', 'contenido', 'duracion', 'orden', 'curso_id', 'estado'];
}
