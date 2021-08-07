<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces\Factory;

use Amp\Http\Client\Request;

interface RequestFactoryInterface
{
    public function create(string $path, string $method, array $body): Request;
}
