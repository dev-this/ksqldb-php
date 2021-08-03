<?php
declare(strict_types=1);

namespace DevThis\KsqlDB\Interfaces;

use DevThis\KsqlDB\StreamEvent;
use DevThis\KsqlDB\StreamHeader;

interface StreamCallback
{
    public function onHeader(StreamHeader $header): void;

    public function onEvent(StreamEvent $event): void;
}
