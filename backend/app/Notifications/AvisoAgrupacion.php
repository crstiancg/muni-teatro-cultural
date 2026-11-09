<?php

namespace App\Notifications;

use App\Models\Agrupacion;
use Illuminate\Notifications\Notification;

// avisos del flujo de agrupaciones; "tipo" le dice a la campanita a dónde llevar:
//   agrupacion_solicitud / agrupacion_actualizacion -> admin (revisar la agrupación)
//   agrupacion_union -> representante (alguien pidió unirse)
//   agrupacion_aprobado / agrupacion_observado / agrupacion_aceptado -> artista (Mis agrupaciones)
class AvisoAgrupacion extends Notification
{
    public function __construct(
        private string $tipo,
        private string $mensaje,
        private Agrupacion $agrupacion,
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
            'agrupacion_id' => $this->agrupacion->id,
            'agrupacion_nombre' => $this->agrupacion->nombre,
        ];
    }
}
