<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class ConsumeDiagramQueue extends Command
{
    protected $signature = 'rabbitmq:consume';

    public function handle()
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', 'rabbitmq'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'user'),
            env('RABBITMQ_PASSWORD', 'password')
        );
        $channel = $connection->channel();
        $channel->queue_declare('diagram_processing', false, true, false, false);

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);
            $this->processMessage($data, $msg);
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume('diagram_processing', '', false, false, false, false, $callback);

        while ($channel->is_consuming() && !app()->runningUnitTests()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }

    public function processMessage(array $data, $msg = null)
    {
        $id = $data['id'];
        $mimeType = $data['mime_type'];
        $base64 = $data['base64'];

        Http::post('http://api-gateway:8000/api/webhook/status', [
            'id' => $id,
            'status' => 'Em processamento'
        ]);

        $geminiPayload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => "Você é um arquiteto de software. Analise o diagrama. Retorne APENAS um JSON válido seguindo EXATAMENTE esta estrutura, sem blocos de código markdown, com as chaves em minúsculo e no plural:\n{\n\"componentes\": [\"item 1\"],\n\"riscos\": [\"item 1\"],\n\"recomendacoes\": [\"item 1\"]\n}"],
                        [
                            'inlineData' => [
                                'mimeType' => $mimeType,
                                'data' => $base64
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $apiKey = env('GEMINI_API_KEY', 'fake-key');

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json'
            ])->timeout(60)->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", $geminiPayload);

            if (!$response->successful()) {
                throw new \Exception("Erro na API da IA");
            }

            $resultText = $response->json('candidates.0.content.parts.0.text');
            $cleanJson = trim(str_replace(['```json', '```'], '', $resultText));
            $aiResultArray = json_decode($cleanJson, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Erro no Parse do JSON: " . json_last_error_msg());
            }

            $reportConnection = new AMQPStreamConnection(
                env('RABBITMQ_HOST', 'rabbitmq'),
                env('RABBITMQ_PORT', 5672),
                env('RABBITMQ_USER', 'user'),
                env('RABBITMQ_PASSWORD', 'password')
            );
            $reportChannel = $reportConnection->channel();
            $reportChannel->queue_declare('report_queue', false, true, false, false);

            $reportData = json_encode([
                'id' => $id,
                'file_path' => $data['file_path'] ?? 'diagrama_processado',
                'components' => $aiResultArray['componentes'] ?? [],
                'risks' => $aiResultArray['riscos'] ?? [],
                'recommendations' => $aiResultArray['recomendacoes'] ?? []
            ]);

            $reportMsg = new AMQPMessage($reportData, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
            $reportChannel->basic_publish($reportMsg, '', 'report_queue');

            $reportChannel->close();
            $reportConnection->close();

            Http::post('http://api-gateway:8000/api/webhook/status', [
                'id' => $id,
                'status' => 'Analisado'
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'Erro na API da IA') || str_contains($errorMessage, '503')) {
                if ($msg) {
                    sleep(10);
                    $msg->nack(true);
                }
                return;
            }

            Http::post('http://api-gateway:8000/api/webhook/status', [
                'id' => $id,
                'status' => 'Erro'
            ]);
        }

        if ($msg) {
            $msg->ack();
        }
    }
}
