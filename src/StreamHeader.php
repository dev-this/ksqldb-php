<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

class StreamHeader
{
    public function __construct(private string $queryId, private array $columnNames, private array $columnTypes)
    {
    }

    public function getColumns(): array
    {
        return \array_combine($this->columnNames, $this->columnTypes);
    }

    public function getQueryId(): string
    {
        return $this->queryId;
    }
}
