<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use App\Domain\Ports\DiagramQueuePort;
use Mockery\MockInterface;

class DiagramUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_deve_rejeitar_arquivo_com_extensao_invalida(): void
    {
        $file = UploadedFile::fake()->create('documento.txt', 100, 'text/plain');

        $response = $this->postJson('/api/upload', [
            'diagram' => $file,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['diagram']);
    }

    public function test_deve_aceitar_diagrama_salvar_no_banco_e_enviar_para_fila(): void
    {
        $this->mock(DiagramQueuePort::class, function (MockInterface $mock) {
            $mock->shouldReceive('publish')->once();
        });

        $file = UploadedFile::fake()->create('minha_arquitetura.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/upload', [
            'diagram' => $file,
        ]);

        $response->assertStatus(202)
                 ->assertJsonStructure(['message', 'analysis_id']);

        $this->assertDatabaseHas('diagram_analyses', [
            'file_path' => 'minha_arquitetura.pdf',
            'status' => 'Recebido'
        ]);
    }

    public function test_deve_retornar_o_status_da_analise(): void
    {
        $analysis = \App\Models\DiagramAnalysis::create([
            'file_path' => 'arquitetura.pdf',
            'status' => 'Em processamento'
        ]);

        $response = $this->getJson("/api/status/{$analysis->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'id' => $analysis->id,
                     'status' => 'Em processamento'
                 ]);
    }

    public function test_deve_atualizar_o_status_via_webhook(): void
    {
        $analysis = \App\Models\DiagramAnalysis::create([
            'file_path' => 'arquitetura.pdf',
            'status' => 'Em processamento'
        ]);

        $response = $this->postJson('/api/webhook/status', [
            'id' => $analysis->id,
            'status' => 'Analisado'
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Status atualizado com sucesso']);

        $this->assertDatabaseHas('diagram_analyses', [
            'id' => $analysis->id,
            'status' => 'Analisado'
        ]);
    }
}
