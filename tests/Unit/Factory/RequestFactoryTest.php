<?php
declare(strict_types=1);

namespace Unit\Factory;

use Amp\Http\Client\Body\StringBody;
use DevThis\KsqlDB\Factory\RequestFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DevThis\KsqlDB\Factory\RequestFactory
 */
class RequestFactoryTest extends TestCase
{
    /**
     * Fragile test which appears to test Amp Request more than the unit itself
     */
    public function testCreateRequestArguments(): void
    {
        $actual = (new RequestFactory('https://example.com'))
            ->create('/query-stream', 'POST', []);

        $this->assertSame('POST', $actual->getMethod());
        $this->assertSame('https://example.com/query-stream', (string)$actual->getUri());
        $this->assertEquals(new StringBody('{}'), $actual->getBody());
    }
}
