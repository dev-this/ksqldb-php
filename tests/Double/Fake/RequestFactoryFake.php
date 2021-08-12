<?php
declare(strict_types=1);

namespace Double\Fake;

use Amp\Http\Client\Request;
use DevThis\KsqlDB\Interfaces\Factory\RequestFactoryInterface;

class RequestFactoryFake implements RequestFactoryInterface
{
    public function __construct(private Request $request) {}

    public function create(string $path, string $method, array ...$body): Request
    {
        return $this->request;
    }
}
