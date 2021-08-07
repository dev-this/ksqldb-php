<?php
declare(strict_types=1);

namespace Unit\Factory;

use Amp\Http\Client\HttpClientBuilder;
use DevThis\KsqlDB\Client;
use DevThis\KsqlDB\Factory\ClientFactory;
use DevThis\KsqlDB\Factory\RequestFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DevThis\KsqlDB\Factory\ClientFactory
 */
class ClientFactoryTest extends TestCase
{
    public function testCreateDefaults(): void
    {
        $baseUrl = 'https://example.com';
        $expectation = new Client(HttpClientBuilder::buildDefault(), new RequestFactory($baseUrl));

        $actual = (new ClientFactory())->create($baseUrl);

        $this->assertEquals($expectation, $actual);
    }
}
