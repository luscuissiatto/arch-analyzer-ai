<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Application\UseCases\ProcessDiagramUploadUseCase;
use App\Domain\Ports\DiagramRepositoryPort;

class DiagramUploadController extends Controller
{
    public function __construct(
        private ProcessDiagramUploadUseCase $useCase,
        private DiagramRepositoryPort $repository
    ) {}

    public function upload(Request $request)
    {
        $request->validate([
            'diagram' => 'required|file|mimes:jpeg,png,jpg,pdf|max:10240',
        ]);

        $analysis = $this->useCase->execute($request->file('diagram'));

        return response()->json([
            'message' => 'Upload realizado com sucesso',
            'analysis_id' => $analysis->id
        ], 202);
    }

    public function status($id)
    {
        $analysis = $this->repository->findById($id);

        return response()->json([
            'id' => $analysis->id,
            'status' => $analysis->status
        ]);
    }

    public function webhook(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|string'
        ]);

        $this->repository->updateStatus($request->id, $request->status);

        return response()->json(['message' => 'Status atualizado com sucesso']);
    }
}
