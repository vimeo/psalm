<?php

declare(strict_types=1);

namespace Psalm\Plugin\EventHandler;

use Psalm\Plugin\EventHandler\Event\AfterFileParsedEvent;

/**
 * @api
 * @psalm-mutable
 */
interface AfterFileParsedInterface
{
    /**
     * Called each time the statements of a file are requested, before they are scanned or analyzed
     */
    public static function afterFileParsed(AfterFileParsedEvent $event): void;
}
