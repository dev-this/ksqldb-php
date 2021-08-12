<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Factory;

use Amp\Http\Client\Request;
use DevThis\KsqlDB\Interfaces\Factory\RequestFactoryInterface;
use function array_filter;
use function json_encode;

class RequestFactory implements RequestFactoryInterface
{
    public function __construct(private string $baseUri) {}

    public function create(string $path, string $method, array ...$body): Request
    {
        $path = sprintf('%s%s', rtrim($this->baseUri, '/'), $path);

        $request = new Request($path, $method);
        // HTTP/2 is default request
        $request->setProtocolVersions(['2']);

        // @todo configurable
        $request->setBodySizeLimit(16 * 1024 * 1024); // 128 MB

        // 0 = infinite
        $request->setInactivityTimeout(0);
        $request->setTlsHandshakeTimeout(0);
        $request->setTransferTimeout(0);

        if (count($body) > 1) {
            $request->setBody(implode("\n", array_map(fn($item) => json_encode($item), $body)));

            return $request;
        }

        $request->setBody(json_encode((object)implode("\n",array_filter($body[0] ?? []))));

        return $request;
    }
}
