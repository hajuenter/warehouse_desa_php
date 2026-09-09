<?php

namespace Tests\Integration\Services;

use App\Services\WebhookService;
use Exception;
use PHPUnit\Framework\TestCase;

class WebhookServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_ENV['DESA_KODE'] = '3518131001';
        $_ENV['WEBHOOK_TOKEN'] = 'test-token-123';
        $_ENV['WEBHOOK_TIMEOUT'] = '2';

        /*
         * Gunakan 127.0.0.1:19999 (tidak listening)
         * agar Guzzle langsung throw ConnectionException.
         */
        $_ENV['WEBHOOK_URL'] = 'http://127.0.0.1:19999/api/webhook';
    }

    /*----------------------------------------------------------------------+
    |  send() — EXCEPTION HANDLING                                          |
    +----------------------------------------------------------------------*/

    public function testSendWithUnreachableUrlThrowsException(): void
    {
        $this->expectException(Exception::class);

        WebhookService::send([
            'event_id'   => 'evt-001',
            'table_name' => 'penduduk',
            'event_type' => 'CREATE',
            'record_id'  => '123',
            'payload'    => ['nama' => 'Budi'],
        ]);
    }

    public function testSendWithStringPayloadThrowsExceptionOnUnreachableUrl(): void
    {
        $this->expectException(Exception::class);

        WebhookService::send([
            'event_id'   => 'evt-002',
            'table_name' => 'mutasi',
            'event_type' => 'UPDATE',
            'record_id'  => '456',
            'payload'    => json_encode(['status' => 'pindah']),
        ]);
    }

    public function testSendWithEmptyPayloadThrowsExceptionOnUnreachableUrl(): void
    {
        $this->expectException(Exception::class);

        WebhookService::send([
            'event_id'   => 'evt-003',
            'table_name' => 'keluarga',
            'event_type' => 'DELETE',
            'record_id'  => '789',
            'payload'    => [],
        ]);
    }

    public function testSendExceptionContainsErrorInfo(): void
    {
        try {
            WebhookService::send([
                'event_id'   => 'evt-004',
                'table_name' => 'penduduk',
                'event_type' => 'CREATE',
                'record_id'  => '100',
                'payload'    => ['nik' => '3301234567890001'],
            ]);
            $this->fail('Expected exception was not thrown.');
        } catch (Exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }
    }
}
