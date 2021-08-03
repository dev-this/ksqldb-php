<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

use Amp\Http\Client\HttpClientBuilder;

class ClientFactory
{
    public function create(string $baseUri): Client
    {
        return new Client(
            rtrim($baseUri, '/'),
            HttpClientBuilder::buildDefault(),
            new RequestFactory()
        );
    }
}
