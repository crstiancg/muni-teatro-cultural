<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use App\Http\Requests\StorePerfilPublicoRequest;
use App\Models\Persona;
use App\Notifications\AvisoPerfil;
use Illuminate\Http\Request;

// Flujo del perfil público (bio + redes) y su revisión:
// borrador -> pendiente -> aprobado | observado -> pendiente ...
// Una vez aprobado, los cambios se publican al toque y el admin solo recibe aviso.
class PerfilPublicoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return static::permisos('personas', [
            // updateMio y enviarRevision son del propio artista: no llevan permiso
            'editar' => ['update'],
            'aprobar' => ['aprobar', 'observar'],
        ]);
    }

    // --- el propio artista ---

    public function updateMio(StorePerfilPublicoRequest $request)
    {
        $persona = $this->miPersona($request);
        $this->guardar($request, $persona);

        if ($persona->estado === 'aprobado' && $persona->wasChanged(['biografia', 'redes_sociales'])) {
            $persona->notificarAdmins('actualizacion', "{$persona->nombre_completo} actualizó su perfil público.");
        }

        return response()->json($persona);
    }

    public function enviarRevision(Request $request)
    {
        $persona = $this->miPersona($request);

        abort_unless(
            in_array($persona->estado, ['borrador', 'observado']),
            422,
            $persona->estado === 'pendiente' ? 'Tu solicitud ya está en revisión.' : 'Tu perfil ya está publicado.'
        );
        // sin comisión no hay dónde mostrarlo en el portal
        abort_unless($persona->codigo_comision, 422, 'Primero completa tu comisión en "Editar".');

        $persona->update(['estado' => 'pendiente', 'observacion' => null]);
        $this->registrarRevision($request, $persona, 'enviado');
        $persona->notificarAdmins('solicitud', "{$persona->nombre_completo} envió su perfil para revisión.");

        return response()->json($this->conHistorial($persona));
    }

    // --- el admin ---

    public function update(StorePerfilPublicoRequest $request, Persona $persona)
    {
        $this->guardar($request, $persona);

        return response()->json($persona);
    }

    public function aprobar(Request $request, Persona $persona)
    {
        abort_unless($persona->codigo_comision, 422, 'La persona no tiene comisión asignada.');

        $this->resolver($request, $persona, 'aprobado', null);
        $this->avisarArtista($persona, 'aprobado', 'Tu perfil fue aprobado y ya aparece en el portal.');

        return response()->json($this->conHistorial($persona));
    }

    public function observar(Request $request, Persona $persona)
    {
        $datos = $request->validate(['observacion' => 'required|string|max:1000']);

        $this->resolver($request, $persona, 'observado', $datos['observacion']);
        $this->avisarArtista($persona, 'observado', "Tu perfil fue observado: {$datos['observacion']}");

        return response()->json($this->conHistorial($persona));
    }

    // --- helpers ---

    private function miPersona(Request $request): Persona
    {
        $persona = $request->user()->persona;
        abort_unless($persona, 404);

        return $persona;
    }

    private function guardar(StorePerfilPublicoRequest $request, Persona $persona): void
    {
        $persona->update([
            'biografia' => Persona::biografiaLimpia($request->input('biografia')),
            // se descartan las redes vacías para no guardar { "tiktok": null }
            'redes_sociales' => array_filter($request->input('redes_sociales', [])) ?: null,
        ]);
    }

    private function resolver(Request $request, Persona $persona, string $estado, ?string $observacion): void
    {
        $persona->update([
            'estado' => $estado,
            'observacion' => $observacion,
            'revisado_por' => $request->user()->id,
            'revisado_en' => now(),
        ]);

        $this->registrarRevision($request, $persona, $estado, $observacion);
    }

    private function registrarRevision(Request $request, Persona $persona, string $accion, ?string $observacion = null): void
    {
        $persona->revisiones()->create([
            'accion' => $accion,
            'observacion' => $observacion,
            'user_id' => $request->user()->id,
        ]);
    }

    // el front mezcla la respuesta en la persona: así la línea de tiempo se
    // actualiza sin recargar la página
    private function conHistorial(Persona $persona): Persona
    {
        return $persona->load('revisiones.usuario:id,name');
    }

    private function avisarArtista(Persona $persona, string $tipo, string $mensaje): void
    {
        $persona->user?->notify(new AvisoPerfil($tipo, $mensaje, $persona));
    }
}
