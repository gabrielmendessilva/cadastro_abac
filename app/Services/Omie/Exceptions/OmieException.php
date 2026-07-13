<?php

namespace App\Services\Omie\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class OmieException extends RuntimeException
{
    /** @var array<string,mixed>|null */
    public ?array $payload = null;

    public ?int $httpStatus = null;

    public string $omieCall;

    public function __construct(string $message, string $omieCall, ?int $httpStatus = null, ?array $payload = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->omieCall = $omieCall;
        $this->httpStatus = $httpStatus;
        $this->payload = $payload;
    }

    public static function fromHttp(string $call, RequestException $e): self
    {
        $body = null;
        try {
            $body = $e->response?->json();
            if (!is_array($body)) {
                $body = null;
            }
        } catch (\Throwable) {
            $body = null;
        }

        $status = $e->response?->status();
        $msg = 'Falha HTTP na chamada Omie ' . $call
            . ($status ? " (status {$status})" : '');

        return new self($msg, $call, $status, $body, $e);
    }

    /**
     * @param array<string,mixed> $payload  Corpo devolvido pela Omie contendo faultstring
     */
    public static function fromFault(string $call, array $payload): self
    {
        $fault = (string) ($payload['faultstring'] ?? 'Erro lógico Omie sem descrição.');
        $code  = (string) ($payload['faultcode']   ?? 'UNKNOWN');
        $msg = "Omie retornou fault em {$call}: [{$code}] {$fault}";

        return new self($msg, $call, 200, $payload, null);
    }
}
