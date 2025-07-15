<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NuevaInscripcion extends Notification
{
    use Queueable;

    public $datos;

    /**
     * Create a new notification instance.
     */
    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Inscripción')
            ->line('¡Tienes una nueva inscripción!')
            ->line('Curso: ' . $this->datos['curso'])
            ->line('Alumno: ' . $this->datos['alumno'])
            ->action('Ver inscripciones', url('/'))
            ->line('¡Gracias por usar MINDSCHOOL!');
    }

    public function toDatabase(object $notifiable)
    {
        return [
            'curso' => $this->datos['curso'],
            'alumno' => $this->datos['alumno'],
            'mensaje' => 'Nueva inscripción en el curso ' . $this->datos['curso'],
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
