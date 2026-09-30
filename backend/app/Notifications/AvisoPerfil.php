<?php

namespace App\Notifications;

use App\Models\Persona;
use Illuminate\Notifications\Notification;

// un solo tipo de aviso para todo el flujo de revisión del perfil público;
// "tipo" le dice al front a dónde llevar al hacer clic
class AvisoPerfil extends Notification
{
    public function __construct(
        private string $tipo,
        private string $mensaje,
        private Persona $persona,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
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
}
