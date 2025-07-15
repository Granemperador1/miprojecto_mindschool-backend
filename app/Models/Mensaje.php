<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Mensaje extends Model
{
    use HasFactory;
    
    protected $fillable = ['remitente_id', 'destinatario_id', 'asunto', 'contenido', 'tipo', 'estado', 'fecha_envio', 'fecha_lectura'];
    
    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_lectura' => 'datetime',
    ];
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($mensaje) {
            if (empty($mensaje->fecha_envio)) {
                $mensaje->fecha_envio = now();
            }
        });
    }
}
