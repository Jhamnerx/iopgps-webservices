<?php

namespace App\Services\Senders;

interface UnitSenderInterface
{
    public function send(array $formattedData, $url): void; // Enviar los datos al servicio web
}
