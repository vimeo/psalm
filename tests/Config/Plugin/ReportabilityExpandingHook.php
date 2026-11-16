<?php

declare(strict_types=1);

namespace Psalm\Tests\Config\Plugin;

use Override;
use Psalm\Codebase;
use Psalm\Plugin\EventHandler\AfterCodebasePopulatedInterface;
use Psalm\Plugin\EventHandler\AfterFileAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterCodebasePopulatedEvent;
use Psalm\Plugin\EventHandler\Event\AfterFileAnalysisEvent;

/**
 * Makes a file reportable once the codebase is populated or, in the middle of the analysis, once another file has
 * been analyzed
 */
final class ReportabilityExpandingHook implements AfterCodebasePopulatedInterface, AfterFileAnalysisInterface
{
    /**
     * @var 'none'|'addFilesToAnalyze'|'addFilesToShowResults'|'addReportableFiles'
     */
    public static string $api = 'addReportableFiles';

    public static string $file_to_report = '';

    /**
     * The file whose analysis is followed by the expansion; empty: the expansion follows the population
     */
    public static string $after_file = '';

    public static bool $enabled = false;

    #[Override]
    public static function afterCodebasePopulated(AfterCodebasePopulatedEvent $event): void
    {
        if (self::$enabled && self::$after_file === '') {
            self::expand($event->getCodebase());
        }
    }

    #[Override]
    public static function afterAnalyzeFile(AfterFileAnalysisEvent $event): void
    {
        if (self::$enabled
            && self::$after_file !== ''
            && $event->getStatementsSource()->getFilePath() === self::$after_file
        ) {
            self::expand($event->getCodebase());
        }
    }

    public static function reset(): void
    {
        self::$enabled = false;
        self::$after_file = '';
        self::$file_to_report = '';
    }

    private static function expand(Codebase $codebase): void
    {
        $files = [self::$file_to_report => self::$file_to_report];

        match (self::$api) {
            'none' => null,
            'addFilesToAnalyze' => $codebase->analyzer->addFilesToAnalyze($files),
            'addFilesToShowResults' => $codebase->analyzer->addFilesToShowResults($files),
            'addReportableFiles' => $codebase->addReportableFiles([self::$file_to_report]),
        };
    }
}
