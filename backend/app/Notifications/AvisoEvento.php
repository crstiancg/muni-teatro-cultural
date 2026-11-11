<?php

namespace App\Notifications;

use App\Models\Evento;
use Illuminate\Notifications\Notification;

// avisos del flujo de eventos propuestos; "tipo" le dice a la campanita a dónde llevar:
//   evento_solicitud / evento_actualizacion -> admin (bandeja de la agenda)
//   evento_aprobado / evento_observado -> organizador (Mis eventos)
class AvisoEvento extends Notification
{
    public function __construct(
        private string $tipo,
        private string $mensaje,
        private Evento $evento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => $this->tipo,
            'mensaje' => $this->mensaje,
            'evento_id' => $this->evento->id,
            'evento_titulo' => $this->evento->titulo,
        ];
    }
}
