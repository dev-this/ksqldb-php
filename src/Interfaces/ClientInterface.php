<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces;

use Amp\Promise;
use ArrayObject;
use DevThis\KsqlDB\RunningStream;
use DevThis\KsqlDB\Statement;

interface ClientInterface
{
    public function execute(Statement $statement): ArrayObject;

    public function executeAsync(Statement $statement): Promise;

    //public function stream(Statement $query): iterable;

    public function streamAsync(Statement $query, StreamCallback $handler): RunningStream;

    public function terminateStream(string $queryId): void;
}
