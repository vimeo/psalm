<?php

declare(strict_types=1);

namespace Psalm\Tests\fixtures\ScanHookAndAutoloadFiles;

use Psalm\Plugin\EventHandler\AfterClassLikeVisitInterface;
use Psalm\Plugin\EventHandler\Event\AfterClassLikeVisitEvent;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\RegistrationInterface;
use SimpleXMLElement;

final class ScanHookPlugin implements PluginEntryPointInterface, AfterClassLikeVisitInterface
{
    public function __invoke(RegistrationInterface $registration, ?SimpleXMLElement $config = null): void
    {
        $registration->registerHooksFromClass(self::class);
    }

    public static function afterClassLikeVisit(AfterClassLikeVisitEvent $event): void
    {
        $event->getStorage()->custom_metadata['seen_by_scan_hook'] = true;
    }
}
