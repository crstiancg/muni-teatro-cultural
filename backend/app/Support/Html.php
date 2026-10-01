<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

// HTML que escriben los usuarios con el editor (QEditor): biografías y
// descripciones de actividades. Se limpia SIEMPRE en el backend antes de
// guardar, porque el portal lo muestra con v-html.
class Html
{
    public static function limpio(?string $html): ?string
    {
        if (blank(strip_tags($html ?? ''))) {
            return null;
        }

        return Purifier::clean($html, [
            // "div": QEditor (contenteditable) arma los saltos de línea con div, no con p
            'HTML.Allowed' => 'p,div,br,strong,b,em,i,u,ul,ol,li,a[href],blockquote',
            'HTML.TargetBlank' => true,
            'HTML.Nofollow' => true,
            'AutoFormat.RemoveEmpty' => true,
        ]);
    }

    // para alt de imágenes, vistas previas y meta tags
    public static function texto(?string $html): string
    {
        $conSaltos = preg_replace('/<\/(p|div|li|blockquote)>|<br\s*\/?>/i', ' ', $html ?? '');

        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($conSaltos))));
    }
}
