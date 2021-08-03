<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

use Amp\Http\Client\Request;

class RequestFactory
{
    public function create(string $path, string $method, string $body): Request
    {
        $request = new Request($path, $method);

        $request->setProtocolVersions(['2']);
        $request->setTransferTimeout(0);
        $request->setInactivityTimeout(0);
        $request->setTlsHandshakeTimeout(0);
        $request->setBodySizeLimit(16 * 1024 * 1024); // 128 MB
        $request->setInactivityTimeout(0);
        $request->setBody($body);

        return $request;
    }
}
