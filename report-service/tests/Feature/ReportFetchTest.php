<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\DiagramAnalysis;

class ReportFetchTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_retornar_relatorio_com_sucesso_quando_encontrado(): void
    {
        $analysis = DiagramAnalysis::create([
            'file_path' => 'arquitetura.pdf',
            'status' => 'Analisado',
            'components' => json_encode(['API Gateway', 'Lambda']),
            'risks' => json_encode(['Ponto único de falha']),
            'recommendations' => json_encode(['Usar Multi-AZ'])
        ]);

        $response = $this->getJson("/api/reports/{$analysis->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'components',
                     'risks',
                     'recommendations'
                 ]);
    }

    public function test_deve_retornar_erro_404_quando_relatorio_nao_existir(): void
    {
        $response = $this->getJson('/api/reports/99999');

        $response->assertStatus(404);
    }
}
