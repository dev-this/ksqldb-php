<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

use Amp\Promise;
use Closure;
use function Amp\call;

final class RunningStream implements Promise
{
    private Promise $promise;

    public function __construct(Closure $streamingBody, private string $queryId) {
        $this->promise = call($streamingBody);
        Promise\rethrow($this->promise);
    }

    public function getQueryId(): string {
        return $this->queryId;
    }

    public function terminate(): void
    {

    }

    public function onResolve(callable $onResolved)
    {
        return $this->promise;
    }
}
