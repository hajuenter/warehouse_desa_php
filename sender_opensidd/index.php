<?php

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\QueueService;
use App\Services\WebhookService;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Queue Worker Started\n";
echo "Desa   : " . $_ENV['DESA_KODE'] . "\n";
echo "Target : " . $_ENV['WEBHOOK_URL'] . "\n";

$pollInterval = (int)($_ENV['POLL_INTERVAL'] ?? 3);

while (true) {
    try {
        $queues = QueueService::getPendingQueues();

        if (count($queues) > 0) {
            echo "\n" . date('Y-m-d H:i:s') . "\n";
            echo "Menemukan " . count($queues) . " queue\n";

            foreach ($queues as $queue) {
                echo "Queue ID : {$queue['id']}\n";
                echo "Table    : {$queue['table_name']}\n";
                echo "Event    : {$queue['event_type']}\n";

                try {
                    $result = WebhookService::send($queue);
                    QueueService::markSuccess((int)$queue['id']);
                    echo "Status -> success\n";
                } catch (\Exception $e) {
                    QueueService::markFailed((int)$queue['id'], $e->getMessage());
                    echo "Webhook gagal: {$e->getMessage()}\n";
                }
            }
        } else {
            echo date('H:i:s') . " Tidak ada queue.\n";
        }
    } catch (\Exception $e) {
        echo "Worker error: {$e->getMessage()}\n";
    }

    sleep($pollInterval);
}
