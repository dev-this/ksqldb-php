<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Factory;

use Amp\Http\Client\HttpClientBuilder;
use DevThis\KsqlDB\Client;
use DevThis\KsqlDB\Interfaces\Factory\ClientFactoryInterface;

class ClientFactory implements ClientFactoryInterface
{
    public function create(string $baseUri): Client
    {
        return new Client(
            HttpClientBuilder::buildDefault(),
            new RequestFactory($baseUri)
        );
    }
}
