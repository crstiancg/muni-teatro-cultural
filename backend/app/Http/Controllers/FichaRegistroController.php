<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Support\FichaRegistro;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Symfony\Component\HttpFoundation\Response;

// Ficha de Registro de Agente Cultural: existe desde la primera aprobación del
// perfil (Persona::asignarRegistro) y se verifica en público escaneando su QR.
class FichaRegistroController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        // mia() y verificar() no llevan permiso: la primera es del propio artista,
        // la segunda es pública
        return static::permisos('personas', ['index' => ['show']]);
    }

    public function mia(Request $request): Response
    {
        $persona = $request->user()->persona;
        abort_unless($persona?->numero_registro, 404, 'Tu perfil todavía no fue aprobado: la ficha se genera al aprobarse.');

        return $this->pdf($persona);
    }

    public function show(Persona $persona): Response
    {
        abort_unless($persona->numero_registro, 404, 'Esta persona todavía no está inscrita en el registro.');

        return $this->pdf($persona);
    }

    // Lo que ve quien escanea el QR. Sin login. Un token que no existe es un 404
    // a secas: no se distingue "no existe" de "existe pero no vigente".
    public function verificar(string $token)
    {
        $persona = Persona::where('qr_token', $token)->whereNotNull('numero_registro')->firstOrFail();

        return response()->json(FichaRegistro::verificacion($persona));
    }

    private function pdf(Persona $persona): Response
    {
        $datos = FichaRegistro::datos($persona);

        return Pdf::loadView('pdf.ficha-registro', $datos)
            ->setPaper('a4')
            // sin esto dompdf incrusta las fuentes DejaVu enteras (~1.4 MB por ficha)
            ->setOption('enable_font_subsetting', true)
            ->download("ficha-registro-{$datos['codigo']}.pdf");
    }
}
