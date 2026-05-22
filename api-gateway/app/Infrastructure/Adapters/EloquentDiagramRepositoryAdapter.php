<?php

namespace App\Infrastructure\Adapters;

use App\Domain\Ports\DiagramRepositoryPort;
use App\Models\DiagramAnalysis;

class EloquentDiagramRepositoryAdapter implements DiagramRepositoryPort
{
    public function create(array $attributes)
    {
        return DiagramAnalysis::create($attributes);
    }

    public function findById(int $id)
    {
        return DiagramAnalysis::findOrFail($id);
    }

    public function updateStatus(int $id, string $status)
    {
        $analysis = DiagramAnalysis::findOrFail($id);
        $analysis->status = $status;
        $analysis->save();

        return $analysis;
    }
}
