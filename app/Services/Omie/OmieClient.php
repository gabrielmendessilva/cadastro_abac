<?php

namespace App\Services\Omie;

use App\Services\Omie\Contracts\OmieClientInterface;
use App\Services\Omie\Exceptions\OmieException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Client HTTP para a API Omie.
 *
 * Substitui o antigo `OmieConnector` do abac_admin, que tinha 5 métodos idênticos
 * variando só pelo `$body['call']`. Aqui está unificado em `request()`, com:
 *  - timeout explícito (não travar indefinidamente)
 *  - tratamento de HTTP error (RequestException → OmieException)
 *  - tratamento de faultstring (Omie devolve 200 em erro lógico — origem ignorava)
 *  - logging estruturado sem PII no canal 'omie'
 */
class OmieClient implements OmieClientInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly LoggerInterface $logger,
        private readonly string $baseUrl,
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly int $timeout = 30,
    ) {}

    public function request(string $endpoint, string $call, array $param): array
    {
        $body = [
            'call'       => $call,
            'app_key'    => $this->appKey,
            'app_secret' => $this->appSecret,
            'param'      => [$param],
        ];

        try {
            $response = $this->http
                ->baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->post($endpoint, $body)
                ->throw();
        } catch (RequestException $e) {
            $this->logger->error('omie.http_error', [
                'call'     => $call,
                'endpoint' => $endpoint,
                'status'   => $e->response?->status(),
            ]);

            throw OmieException::fromHttp($call, $e);
        }

        $data = $response->json();
        if (!is_array($data)) {
            $data = [];
        }

        // Omie retorna 200 com faultstring em erro lógico. Origem ignorava.
        if (isset($data['faultstring'])) {
            $this->logger->warning('omie.fault', [
                'call'       => $call,
                'endpoint'   => $endpoint,
                'faultcode'  => $data['faultcode'] ?? null,
                'faultstring'=> $data['faultstring'],
            ]);

            throw OmieException::fromFault($call, $data);
        }

        $this->logger->info('omie.ok', [
            'call'     => $call,
            'endpoint' => $endpoint,
        ]);

        return $data;
    }
}
