<?php

namespace App\Domain\Ports;

interface DiagramQueuePort
{
    public function publish(int $id, string $mimeType, string $base64): void;
}
