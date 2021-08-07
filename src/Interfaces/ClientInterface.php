<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces;

use Amp\Promise;
use DevThis\KsqlDB\RunningStream;
use DevThis\KsqlDB\Statement;

interface ClientInterface
{
    //public function execute(Statement $statement): iterable;

    public function executeAsync(Statement $statement): Promise;

    //public function stream(Statement $query): iterable;

    public function streamAsync(Statement $query, StreamCallback $handler): RunningStream;
}
