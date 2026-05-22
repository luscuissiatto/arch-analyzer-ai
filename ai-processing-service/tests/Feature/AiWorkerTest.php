<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Console\Commands\ConsumeDiagramQueue;

class AiWorkerTest extends TestCase
{
    public function test_deve_configurar_worker_e_encerrar_em_modo_de_teste(): void
    {
        $command = new ConsumeDiagramQueue();
        $command->handle();

        $this->assertTrue(true);
    }

    public function test_deve_processar_diagrama_com_sucesso(): void
    {
        Http::fake([
            'http://api-gateway:8000/api/webhook/status' => Http::response(['message' => 'ok'], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"componentes":["API Gateway"],"riscos":["Nenhum"],"recomendacoes":["Manter"]}']
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $command = new ConsumeDiagramQueue();

        $msgMock = \Mockery::mock(\PhpAmqpLib\Message\AMQPMessage::class);
        $msgMock->shouldReceive('ack')->once();

        $data = [
            'id' => 123,
            'file_path' => 'arquitetura.pdf',
            'mime_type' => 'application/pdf',
            'base64' => 'JVBERi0xLjQKJ...'
        ];

        $command->processMessage($data, $msgMock);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api-gateway:8000/api/webhook/status' &&
                   $request['status'] == 'Em processamento';
        });

        $this->assertTrue(true);
    }

    public function test_deve_retornar_erro_quando_json_da_ia_for_invalido(): void
    {
        Http::fake([
            'http://api-gateway:8000/api/webhook/status' => Http::response(['message' => 'ok'], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Isto nao e um JSON valido']
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $command = new ConsumeDiagramQueue();

        $msgMock = \Mockery::mock(\PhpAmqpLib\Message\AMQPMessage::class);
        $msgMock->shouldReceive('ack')->once();

        $data = [
            'id' => 123,
            'file_path' => 'arquitetura.pdf',
            'mime_type' => 'application/pdf',
            'base64' => 'JVBERi0xLjQKJ...'
        ];

        $command->processMessage($data, $msgMock);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api-gateway:8000/api/webhook/status' &&
                   $request['status'] == 'Erro';
        });
    }

    public function test_deve_fazer_requeue_quando_ia_retornar_erro_503(): void
    {
        Http::fake([
            'http://api-gateway:8000/api/webhook/status' => Http::response(['message' => 'ok'], 200),
            'https://generativelanguage.googleapis.com/*' => Http::response('Service Unavailable', 503),
        ]);

        $command = new ConsumeDiagramQueue();

        $msgMock = \Mockery::mock(\PhpAmqpLib\Message\AMQPMessage::class);
        $msgMock->shouldReceive('nack')->with(true)->once();

        $data = [
            'id' => 123,
            'file_path' => 'arquitetura.pdf',
            'mime_type' => 'application/pdf',
            'base64' => 'JVBERi0xLjQKJ...'
        ];

        $command->processMessage($data, $msgMock);

        $this->assertTrue(true);
    }
}
