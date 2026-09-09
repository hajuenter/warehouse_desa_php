<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class WebhookService
{
    public static function send(array $queue): array
    {
        $client = new Client();

        $rawPayload = $queue['payload'];
        $payload = is_string($rawPayload) ? json_decode($rawPayload, true) : $rawPayload;

        try {
            $response = $client->post($_ENV['WEBHOOK_URL'], [
                'json' => [
                    'kode_desa'  => $_ENV['DESA_KODE'],
                    'event_id'   => $queue['event_id'],
                    'table'      => $queue['table_name'],
                    'action'     => $queue['event_type'],
                    'record_id'  => $queue['record_id'],
                    'payload'    => $payload,
                ],
                'timeout' => (int)($_ENV['WEBHOOK_TIMEOUT'] ?? 10),
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['WEBHOOK_TOKEN'],
                    'Content-Type'  => 'application/json',
                ],
            ]);

            return json_decode($response->getBody()->getContents(), true) ?? [];
        } catch (GuzzleException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}