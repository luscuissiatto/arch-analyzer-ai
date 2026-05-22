<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DiagramAnalysis;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class ConsumeReports extends Command
{
    protected $signature = 'rabbitmq:consume-reports';

    public function handle()
    {
        $connection = new AMQPStreamConnection(env('RABBITMQ_HOST', 'rabbitmq'), 5672, env('RABBITMQ_USER', 'user'), env('RABBITMQ_PASSWORD', 'password'));
        $channel = $connection->channel();

        $channel->queue_declare('report_queue', false, true, false, false);

        $callback = function ($msg) {
            $data = json_decode($msg->body, true);

            DiagramAnalysis::updateOrCreate(
                ['id' => $data['id']],
                [
                    'file_path' => $data['file_path'],
                    'status' => 'Analisado',
                    'components' => json_encode($data['components']),
                    'risks' => json_encode($data['risks']),
                    'recommendations' => json_encode($data['recommendations']),
                ]
            );

            $msg->ack();
        };

        $channel->basic_consume('report_queue', '', false, false, false, false, $callback);

        while ($channel->is_open()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}
