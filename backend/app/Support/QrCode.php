<?php

namespace App\Support;

use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode as Generador;
use chillerlan\QRCode\QROptions;

// QR como data URI SVG: nítido a cualquier tamaño de impresión.
// Mismo criterio que el sistema de licencias (muni-ac-licencias).
final class QrCode
{
    public static function svg(string $datos): string
    {
        $opciones = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => true,
            'addQuietzone' => true,
            'quietzoneSize' => 1,
        ]);

        return (new Generador($opciones))->render($datos);
    }
}
