<?php

declare(strict_types=1);

namespace Bnomei\KirbyMcp\Mcp;

use GuzzleHttp\Psr7\HttpFactory;
use Mcp\Server\Session\FileSessionStore;
use Psr\Http\Message\ResponseInterface;

final class HttpMcpListener
{
    /**
     * @var array<int, true>
     */
    private array $children = [];

    public function __construct(
        private readonly ServerFactory $serverFactory = new ServerFactory(),
    ) {
    }

    /**
     * @param array<int, string> $allowedOrigins
     */
    public function serve(
        string $host,
        int $port,
        string $path,
        string $projectRoot,
        ?string $sharedToken = null,
        array $allowedOrigins = [],
        bool $once = false,
        int $sseMaxSeconds = 300,
    ): int {
        $address = sprintf('tcp://%s:%d', $host, $port);
        $errno = 0;
        $errstr = '';
        $server = @stream_socket_server($address, $errno, $errstr);

        if ($server === false) {
            fwrite(STDERR, sprintf("Kirby MCP http failed to bind %s: %s\n", $address, $errstr !== '' ? $errstr : (string) $errno));

            return 1;
        }

        $sessionDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.kirby-mcp' . DIRECTORY_SEPARATOR . 'http-sessions';
        $sessionStore = new FileSessionStore($sessionDir);
        $factory = new HttpFactory();
        $tracer = new HttpMcpTracer(
            serverFactory: $this->serverFactory,
            sessionStore: $sessionStore,
            path: $path,
            responseFactory: $factory,
            streamFactory: $factory,
            sharedToken: $sharedToken,
            allowedOrigins: $allowedOrigins,
            sseMaxSeconds: $sseMaxSeconds,
        );

        fwrite(STDERR, sprintf("Kirby MCP HTTP listening on http://%s:%d%s\n", $host, $port, $path));

        do {
            $this->reapChildren();

            $client = @stream_socket_accept($server, -1);
            if ($client === false) {
                continue;
            }

            $request = $this->readRequest($client, $factory);
            if ($request === null) {
                fclose($client);
                continue;
            }

            $response = $tracer->handle($request);
            if (
                $once === false
                && $this->isEventStream($response)
                && function_exists('pcntl_fork')
            ) {
                $pid = pcntl_fork();
                if ($pid === -1) {
                    fwrite(STDERR, "Kirby MCP http failed to fork SSE connection handler.\n");
                    $this->emitResponse($client, $response);
                    fclose($client);

                    continue;
                }

                if ($pid > 0) {
                    $this->children[$pid] = true;
                    fclose($client);

                    continue;
                }

                fclose($server);
                $this->emitResponse($client, $response);
                fclose($client);

                return 0;
            }

            if ($this->emitResponse($client, $response)) {
                fclose($client);
            }
        } while ($once === false);

        fclose($server);
        $this->reapChildren();

        return 0;
    }

    private function readRequest(mixed $client, HttpFactory $factory): ?\Psr\Http\Message\ServerRequestInterface
    {
        $requestLine = fgets($client);
        if (!is_string($requestLine) || trim($requestLine) === '') {
            return null;
        }

        $parts = explode(' ', trim($requestLine), 3);
        if (count($parts) < 2) {
            return null;
        }

        [$method, $target] = $parts;
        $headers = [];

        while (($line = fgets($client)) !== false) {
            $line = rtrim($line, "\r\n");
            if ($line === '') {
                break;
            }

            $separator = strpos($line, ':');
            if ($separator === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));
            if ($name !== '') {
                $headers[$name][] = $value;
            }
        }

        $body = '';
        $contentLength = 0;
        foreach ($headers as $name => $values) {
            if (strcasecmp($name, 'Content-Length') === 0) {
                $contentLength = max(0, (int) ($values[0] ?? 0));
                break;
            }
        }

        while ($contentLength > strlen($body) && !feof($client)) {
            $remaining = $contentLength - strlen($body);
            if ($remaining < 1) {
                break;
            }

            $chunk = fread($client, $remaining);
            if (!is_string($chunk) || $chunk === '') {
                break;
            }
            $body .= $chunk;
        }

        $host = $headers['Host'][0] ?? $headers['host'][0] ?? '127.0.0.1';
        $uri = 'http://' . $host . $target;
        $request = $factory->createServerRequest($method, $uri)
            ->withBody($factory->createStream($body));

        foreach ($headers as $name => $values) {
            $request = $request->withHeader($name, $values);
        }

        return $request;
    }

    private function emitResponse(mixed $client, ResponseInterface $response): bool
    {
        fwrite(
            $client,
            sprintf(
                "HTTP/%s %d %s\r\n",
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
            ),
        );

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                fwrite($client, $name . ': ' . $value . "\r\n");
            }
        }

        $connection = strtolower($response->getHeaderLine('Connection'));
        if ($connection === '') {
            fwrite($client, "Connection: close\r\n");
            $connection = 'close';
        }

        fwrite($client, "\r\n");

        ob_start(static function (string $buffer) use ($client): string {
            if ($buffer !== '') {
                fwrite($client, $buffer);
            }

            return '';
        }, 1);
        $body = (string) $response->getBody();
        ob_end_flush();

        if ($body !== '') {
            fwrite($client, $body);
        }

        return $connection !== 'keep-alive';
    }

    private function isEventStream(ResponseInterface $response): bool
    {
        return str_starts_with(strtolower($response->getHeaderLine('Content-Type')), 'text/event-stream');
    }

    private function reapChildren(): void
    {
        if (!function_exists('pcntl_waitpid')) {
            return;
        }

        foreach (array_keys($this->children) as $pid) {
            $result = pcntl_waitpid($pid, $status, \WNOHANG);
            if ($result === $pid || $result === -1) {
                unset($this->children[$pid]);
            }
        }
    }
}
