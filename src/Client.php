<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

use Amp\CancellationToken;
use Amp\Http\Client\HttpClient;
use Amp\Promise;
use DevThis\KsqlDB\Interfaces\ExecutionCallback;
use DevThis\KsqlDB\Interfaces\StreamCallback;

use function Amp\call;

class Client
{
    public function __construct(private string $baseUri, private HttpClient $ampHttpClient, private RequestFactory $requestFactory)
    {
    }

    public function execute(Statement $statement, ExecutionCallback $handler): Promise
    {
        return call(function() use ($statement, $handler) {
            $request = $this->requestFactory->create(
                sprintf('%s/ksql', $this->baseUri),
                'POST',
                json_encode(\array_filter(['ksql' => $statement->getSql(), 'streamsProperties' => []]))
            );

            $responsePromise = $this->ampHttpClient->request($request);

            /**
             * yield forces promise to wait for header response
             *
             * @var \Amp\Http\Client\Response $response
             */
            $response = yield $responsePromise;

            $body = $response->getBody();

            $something = yield $body->buffer();

            $handler->onResponse(
                json_decode(
                    $something,
                    true,
                    JSON_THROW_ON_ERROR
                )
            );
        });
    }

    public function stream(Statement $query, StreamCallback $handler, ?CancellationToken $cancelToken = null): Promise
    {
        return call(function() use ($handler, $query, $cancelToken) {
            $request = $this->requestFactory->create(
                sprintf('%s/query-stream', $this->baseUri),
                'POST',
                json_encode(\array_filter(['sql' => $query->getSql(), 'streamsProperties' => []]))
            );

            $responsePromise = $this->ampHttpClient->request($request, $cancelToken);

            /**
             * yield forces promise to wait for header response
             *
             * @var \Amp\Http\Client\Response $response
             */
            $response = yield $responsePromise;

            $body = $response->getBody();

            // Fetch first line from stream, to work out column mapping
            if (null !== $chunk = yield $body->read()) {
                $header = json_decode($chunk, true, JSON_THROW_ON_ERROR);

                if (($header['@type'] ?? null) === 'generic_error') {
                    throw new \Exception(sprintf('[%d] %s', $header['error_code'] ?? 0, $header['message'] ?? ''));
                }

                $handler->onHeader(
                    new StreamHeader(
                       $header['queryId'],
                       $header['columnNames'],
                       $header['columnTypes']
                   )
                );
            }

            // Stream indefinitely
            while (null !== $chunk = yield $body->read()) {
                $event = new StreamEvent(json_decode($chunk, true, JSON_THROW_ON_ERROR));
                $handler->onEvent($event);
            }
        });
    }
}
