<?php

namespace App\Domain\Ports;

interface DiagramRepositoryPort
{
    public function create(array $attributes);
    public function findById(int $id);
    public function updateStatus(int $id, string $status);
}
