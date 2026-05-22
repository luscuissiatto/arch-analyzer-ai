<?php

namespace App\Http\Controllers;

use App\Models\DiagramAnalysis;

class ReportController extends Controller
{
    public function show($id)
    {
        $report = DiagramAnalysis::find($id);

        if (!$report) {
            return response()->json(['error' => 'Relatorio ainda não gerado ou não encontrado'], 404);
        }

        return response()->json([
            'components' => json_decode($report->components, true),
            'risks' => json_decode($report->risks, true),
            'recommendations' => json_decode($report->recommendations, true),
        ]);
    }
}
