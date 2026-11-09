<?php

namespace App\Support;

class UserAgent
{
    // bots de buscadores y de vista previa de links (WhatsApp, Facebook, ...)
    private const BOTS = '/bot|crawler|spider|facebookexternalhit|facebot|whatsapp|telegram|slack|discord|linkedin|pinterest|skypeuripreview|embedly|vkshare|preview/i';

    public static function esBot(?string $userAgent): bool
    {
        // sin user-agent casi siempre es un script, no una persona
        return blank($userAgent) || preg_match(self::BOTS, $userAgent) === 1;
    }
}
