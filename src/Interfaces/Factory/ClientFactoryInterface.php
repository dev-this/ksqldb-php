<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces\Factory;

use DevThis\KsqlDB\Client;

interface ClientFactoryInterface
{
    public function create(string $baseUri): Client;
}
