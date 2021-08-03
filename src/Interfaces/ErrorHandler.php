<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces;

interface ErrorHandler {
    public function onError(): void;
}
