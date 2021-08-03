<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces;

interface ExecutionCallback {
    public function onResponse(array $event): void;
}
