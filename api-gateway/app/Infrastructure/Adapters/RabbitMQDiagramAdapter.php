<?php

namespace App\Infrastructure\Adapters;

use App\Domain\Ports\DiagramQueuePort;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQDiagramAdapter implements DiagramQueuePort
{
    public function publish(int $id, string $mimeType, string $base64): void
    {
        $connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST', '127.0.0.1'),
            env('RABBITMQ_PORT', 5672),
            env('RABBITMQ_USER', 'user'),
            env('RABBITMQ_PASSWORD', 'password')
        );

        $channel = $connection->channel();
        $channel->queue_declare('diagram_processing', false, true, false, false);

        $data = json_encode([
            'id' => $id,
            'mime_type' => $mimeType,
            'base64' => $base64
        ]);

        $msg = new AMQPMessage($data, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);

        $channel->basic_publish($msg, '', 'diagram_processing');

        $channel->close();
        $connection->close();
    }
}
