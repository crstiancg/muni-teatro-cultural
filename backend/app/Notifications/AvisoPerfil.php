<?php

namespace App\Notifications;

use App\Models\Persona;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

// un solo tipo de aviso para todo el flujo de revisión del perfil público;
// "tipo" le dice al front a dónde llevar al hacer clic
class AvisoPerfil extends Notification
{
    private const ASUNTOS = [
        'solicitud' => 'Nueva solicitud de revisión de perfil',
        'actualizacion' => 'Un artista actualizó su perfil público',
        'aprobado' => 'Tu perfil fue aprobado',
        'observado' => 'Tu perfil tiene observaciones',
    ];

    public function __construct(
        private string $tipo,
        private string $mensaje,
        private Persona $persona,
    ) {}

    // campanita (database) + mail. Con MAIL_MAILER=log los mails se escriben en
    // storage/logs/laravel.log en vez de enviarse: sirve hasta tener SMTP.
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(self::ASUNTOS[$this->tipo] ?? 'Aviso de tu perfil')
            ->greeting("Hola, {$notifiable->name}")
            ->line($this->mensaje)
            ->action($this->esParaAdmin() ? 'Revisar perfil' : 'Ver mi perfil', $this->enlace());
    }

    public function toArray(object $notifiable): array
    {
        return [
            // solicitud | actualizacion | aprobado | observado
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'persona_id' => $this->persona->id,
            'persona_nombre' => $this->persona->nombre_completo,
        ];
    }

    private function esParaAdmin(): bool
    {
        return in_array($this->tipo, ['solicitud', 'actualizacion']);
    }

    // mismas rutas a las que lleva la campanita en NotificacionesBell.vue
    private function enlace(): string
    {
        $base = config('app.frontend_url');

        return $this->esParaAdmin()
            ? "{$base}/personas/{$this->persona->id}"
            : "{$base}/curriculum-vitae";
    }
}
