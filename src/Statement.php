<?php
declare(strict_types=1);

namespace DevThis\KsqlDB;

class Statement
{
    /**
     * @param string $query
     * @param array<string, string>|null $properties
     */
    public function __construct(private string $query, ?array $properties = null)
    {
    }

    public function getSql(): string
    {
        return $this->query;
    }
}
