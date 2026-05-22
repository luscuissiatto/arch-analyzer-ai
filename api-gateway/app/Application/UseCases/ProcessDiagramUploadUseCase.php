<?php

namespace App\Application\UseCases;

use App\Domain\Ports\DiagramQueuePort;
use App\Domain\Ports\DiagramRepositoryPort;

class ProcessDiagramUploadUseCase
{
    public function __construct(
        private DiagramQueuePort $queuePort,
        private DiagramRepositoryPort $repositoryPort
    ) {}

    public function execute($file)
    {
        $fileName = $file->getClientOriginalName();

        $analysis = $this->repositoryPort->create([
            'file_path' => $fileName,
            'status' => 'Recebido'
        ]);

        $mimeType = $file->getMimeType();
        $base64 = base64_encode($file->get());

        $this->queuePort->publish($analysis->id, $mimeType, $base64);

        return $analysis;
    }
}
