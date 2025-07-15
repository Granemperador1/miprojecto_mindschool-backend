<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatosBancarios extends Model
{
    protected $table = 'datos_bancarios';
    protected $fillable = ['usuario_id', 'banco', 'clabe', 'titular'];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
} 