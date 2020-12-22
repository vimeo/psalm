<?php
namespace Psalm\Plugin\Hook;

use Psalm\Plugin\Hook\Event\AfterClassLikeAnalysisEvent;

interface AfterClassLikeAnalysisInterface
{
    /**
     * Called after a statement has been checked
     *
     * @return null|false
     */
    public static function afterStatementAnalysis(AfterClassLikeAnalysisEvent $event);
}
