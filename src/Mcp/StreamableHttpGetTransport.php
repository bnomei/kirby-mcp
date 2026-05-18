<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use Mcp\Server\Transport\BaseTransport;
use Mcp\Server\Transport\CallbackStream;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

/**
 * @extends BaseTransport<ResponseInterface>
 */
final class StreamableHttpGetTransport extends BaseTransport
{
    private const SESSION_HEADER = 'Mcp-Session-Id';

    public function __construct(
        private readonly Uuid $sessionIdValue,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly int $maxSeconds = 300,
        private readonly int $pollIntervalMicros = 100000,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);
        $this->sessionId = $this->sessionIdValue;
    }

    public function listen(): ResponseInterface
    {
        $stream = new CallbackStream(function (): void {
            $started = microtime(true);
            $sentInitialComment = false;
            $nextHeartbeat = $started + 15.0;

            while (true) {
                $messages = $this->getOutgoingMessages($this->sessionIdValue);

                if ($messages === []) {
                    $now = microtime(true);
                    if ($sentInitialComment === false || $now >= $nextHeartbeat) {
                        echo $sentInitialComment === false ? ": connected\n\n" : ": keepalive\n\n";
                        @ob_flush();
                        flush();
                        $sentInitialComment = true;
                        $nextHeartbeat = $now + 15.0;
                    }
                } else {
                    foreach ($messages as $message) {
                        echo "event: message\n";
                        echo "data: {$message['message']}\n\n";
                        @ob_flush();
                        flush();
                    }
                }

                if ($this->maxSeconds > 0 && microtime(true) - $started >= $this->maxSeconds) {
                    return;
                }

                usleep(max(10000, $this->pollIntervalMicros));
            }
        }, $this->logger);

        return $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no')
            ->withHeader(self::SESSION_HEADER, $this->sessionIdValue->toRfc4122())
            ->withBody($stream);
    }

    public function send(string $data, array $context): void
    {
        $this->logger->debug('Ignoring direct send on HTTP GET SSE transport.', [
            'status_code' => $context['status_code'] ?? null,
            'bytes' => strlen($data),
        ]);
    }
}
