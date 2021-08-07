<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

use Amp\Deferred;
use Amp\Http\Client\HttpClient;
use Amp\Promise;
use ArrayObject;
use DevThis\KsqlDB\Interfaces\Factory\RequestFactoryInterface;
use DevThis\KsqlDB\Interfaces\StreamCallback;
use DevThis\KsqlDB\Interfaces\ClientInterface;
use function Amp\call;

class Client implements ClientInterface
{
    public function __construct(private HttpClient $ampHttpClient, private RequestFactoryInterface $requestFactory)
    {
    }

    public function execute(Statement $statement): ArrayObject
    {
        /** @var ArrayObject $result */
        $result = Promise\wait($this->executeAsync($statement));

        // @todo - not assume things.

        return $result;
    }

    public function executeAsync(Statement $statement): Promise
    {
        $query = rtrim($statement->getSql(), ';');

        $request = $this->requestFactory->create(
            '/ksql',
            'POST',
            ['ksql' => sprintf('%s;', $query), 'streamsProperties' => []]
        );

        $deferred = new Deferred();

        call(function() use ($request, $deferred) {
            $responsePromise = $this->ampHttpClient->request($request);

            /**
             * yield forces promise to wait for header response
             *
             * @var \Amp\Http\Client\Response $response
             */
            $response = yield $responsePromise;

            $event = yield $response->getBody()->buffer();

            $normalised = json_decode(
                $event,
                true,
                JSON_THROW_ON_ERROR
            );

            $deferred->resolve(new ArrayObject($normalised));
        });

        return $deferred->promise();
    }

    public function terminateStream(string $queryId): void
    {
        $request = $this->requestFactory->create(
            '/close-query',
            'POST',
            ['queryId' => $queryId]
        );

        $responsePromise = $this->ampHttpClient->request($request);

        /**
         * yield forces promise to wait for header response
         *
         * @var \Amp\Http\Client\Response $response
         */
        $response = Promise\wait($responsePromise);

        // @todo check response
        Promise\wait($response->getBody()->buffer());
    }

    public function streamAsync(Statement $query, StreamCallback $handler): RunningStream
    {
        $request = $this->requestFactory->create(
            '/query-stream',
            'POST',
            ['sql' => $query->getSql(), 'streamsProperties' => []]
        );

        $responsePromise = $this->ampHttpClient->request($request);

        /**
         * yield forces promise to wait for header response
         *
         * @var \Amp\Http\Client\Response $response
         */
        //$response = yield $responsePromise;
        $response = Promise\wait($responsePromise);

        $header = Promise\wait($response->getBody()->read());
        $header = json_decode($header, true, JSON_THROW_ON_ERROR);

        // Fetch first line from stream, to work out column mapping
/*            if (null !== $chunk = yield $response->getBody()->read()) {
            $header = json_decode($chunk, true, JSON_THROW_ON_ERROR);

            if (($header['@type'] ?? null) === 'generic_error') {
                throw new \Exception(sprintf('[%d] %s', $header['error_code'] ?? 0, $header['message'] ?? ''));
            }
        }*/
        $handler->onHeader(
            new StreamHeader(
                $header['queryId'],
                $header['columnNames'],
                $header['columnTypes']
            )
        );

        $streamingBody = static function() use ($response, $handler) {
            while (null !== $chunk = yield $response->getBody()->read()) {
                $event = new StreamEvent(json_decode($chunk, true, JSON_THROW_ON_ERROR));
                $handler->onEvent($event);
            }
        };

        return new RunningStream($streamingBody, $header['queryId']);
    }
}
