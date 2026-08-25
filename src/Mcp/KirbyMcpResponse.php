<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Kirby\Http\Response as KirbyResponse;
use Psr\Http\Message\ResponseInterface;

final class KirbyMcpResponse extends KirbyResponse
{
    public function __construct(
        private readonly ResponseInterface $response,
    ) {
        parent::__construct(
            body: '',
            type: $this->contentType(),
            code: $response->getStatusCode(),
            headers: self::headersFrom($response),
        );
    }

    public function body(): string
    {
        return (string) $this->response->getBody();
    }

    public function code(): int
    {
        return $this->response->getStatusCode();
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return self::headersFrom($this->response);
    }

    public function send(): string
    {
        if (headers_sent() === false) {
            foreach ($this->response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header($name . ': ' . $value, false);
                }
            }

            // WWW-Authenticate implicitly changes PHP's status to 401, so restore
            // the authoritative PSR response status after emitting all headers.
            http_response_code($this->response->getStatusCode());
        }

        return (string) $this->response->getBody();
    }

    private function contentType(): string
    {
        $contentType = $this->response->getHeaderLine('Content-Type');

        return $contentType !== '' ? $contentType : 'application/octet-stream';
    }

    /**
     * @return array<string, string>
     */
    private static function headersFrom(ResponseInterface $response): array
    {
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }

        return $headers;
    }
}
