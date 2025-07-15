<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Notifications\NuevaInscripcion;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function notify(NuevaInscripcion $notification)
    {
        // Implement the logic to send the notification to the user
    }

    /**
     * Relación con las inscripciones del usuario
     */
    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class);
    }

    /**
     * Relación con las entregas de tareas del usuario
     */
    public function entregasTareas()
    {
        return $this->hasMany(EntregaTarea::class, 'estudiante_id');
    }

    /**
     * Relación con los cursos donde el usuario es instructor
     */
    public function cursosInstructor()
    {
        return $this->hasMany(Curso::class, 'instructor_id');
    }

    /**
     * Relación muchos a muchos con cursos (como alumno) a través de curso_usuario
     */
    public function cursosAlumno()
    {
        return $this->belongsToMany(Curso::class, 'curso_usuario', 'usuario_id', 'curso_id')
            ->withPivot('tipo_acceso', 'fecha_acceso')
            ->withTimestamps();
    }

    /**
     * Relación con la tabla pivote curso_usuario
     */
    public function cursoUsuarios()
    {
        return $this->hasMany(CursoUsuario::class, 'usuario_id');
    }

    /**
     * Relación uno a uno con datos bancarios
     */
    public function datosBancarios()
    {
        return $this->hasOne(DatosBancarios::class, 'usuario_id');
    }
}
