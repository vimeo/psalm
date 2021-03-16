<?php

namespace Psalm\Internal;

use Psalm\Plugin\Hook;
use Psalm\Plugin\EventHandler;
use Psalm\Plugin\EventHandler\Event;
use Psalm\Type\Atomic\TLiteralString;
use function count;
use function is_subclass_of;


class EventDispatcher
{
    /**
     * Static methods to be called after method checks have completed
     *
     * @var list<class-string<EventHandler\AfterMethodCallAnalysisInterface>>
     */
    private $after_method_checks = [];
    /** @var list<class-string<Hook\AfterMethodCallAnalysisInterface>> */
    private $legacy_after_method_checks = [];

    /**
     * Static methods to be called after project function checks have completed
     *
     * Called after function calls to functions defined in the project.
     *
     * Allows influencing the return type and adding of modifications.
     *
     * @var list<class-string<EventHandler\AfterFunctionCallAnalysisInterface>>
     */
    public $after_function_checks = [];
    /** @var list<class-string<Hook\AfterFunctionCallAnalysisInterface>> */
    public $legacy_after_function_checks = [];

    /**
     * Static methods to be called after every function call
     *
     * Called after each function call, including php internal functions.
     *
     * Cannot change the call or influence its return type
     *
     * @var list<class-string<EventHandler\AfterEveryFunctionCallAnalysisInterface>>
     */
    public $after_every_function_checks = [];
    /** @var list<class-string<Hook\AfterEveryFunctionCallAnalysisInterface>> */
    public $legacy_after_every_function_checks = [];

    /**
     * Static methods to be called after expression checks have completed
     *
     * @var list<class-string<EventHandler\AfterExpressionAnalysisInterface>>
     */
    public $after_expression_checks = [];
    /** @var list<class-string<Hook\AfterExpressionAnalysisInterface>> */
    public $legacy_after_expression_checks = [];

    /**
     * Static methods to be called after statement checks have completed
     *
     * @var list<class-string<EventHandler\AfterStatementAnalysisInterface>>
     */
    public $after_statement_checks = [];
    /** @var list<class-string<Hook\AfterStatementAnalysisInterface>> */
    public $legacy_after_statement_checks = [];

    /**
     * Static methods to be called after method checks have completed
     *
     * @var list<class-string<EventHandler\StringInterpreterInterface>>
     */
    public $string_interpreters = [];
    /** @var list<class-string<Hook\StringInterpreterInterface>> */
    public $legacy_string_interpreters = [];

    /**
     * Static methods to be called after classlike exists checks have completed
     *
     * @var list<class-string<EventHandler\AfterClassLikeExistenceCheckInterface>>
     */
    public $after_classlike_exists_checks = [];
    /** @var list<class-string<Hook\AfterClassLikeExistenceCheckInterface>> */
    public $legacy_after_classlike_exists_checks = [];

    /**
     * Static methods to be called after classlike checks have completed
     *
     * @var list<class-string<EventHandler\AfterClassLikeAnalysisInterface>>
     */
    public $after_classlike_checks = [];
    /** @var list<class-string<Hook\AfterClassLikeAnalysisInterface>> */
    public $legacy_after_classlike_checks = [];

    /**
     * Static methods to be called after classlikes have been scanned
     *
     * @var list<class-string<EventHandler\AfterClassLikeVisitInterface>>
     */
    private $after_visit_classlikes = [];
    /** @var list<class-string<Hook\AfterClassLikeVisitInterface>> */
    private $legacy_after_visit_classlikes = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var list<class-string<EventHandler\AfterCodebasePopulatedInterface>>
     */
    public $after_codebase_populated = [];
    /** @var list<class-string<Hook\AfterCodebasePopulatedInterface>> */
    public $legacy_after_codebase_populated = [];

    /**
     * Static methods to be called after codebase has been populated
     *
     * @var list<class-string<EventHandler\AfterAnalysisInterface>>
     */
    public $after_analysis = [];
    /** @var list<class-string<Hook\AfterAnalysisInterface>> */
    public $legacy_after_analysis = [];

    /**
     * Static methods to be called after a file has been analyzed
     *
     * @var list<class-string<EventHandler\AfterFileAnalysisInterface>>
     */
    public $after_file_checks = [];
    /** @var list<class-string<Hook\AfterFileAnalysisInterface>> */
    public $legacy_after_file_checks = [];

    /**
     * Static methods to be called before a file is analyzed
     *
     * @var list<class-string<EventHandler\BeforeFileAnalysisInterface>>
     */
    public $before_file_checks = [];
    /** @var list<class-string<Hook\BeforeFileAnalysisInterface>> */
    public $legacy_before_file_checks = [];

    /**
     * Static methods to be called after functionlike checks have completed
     *
     * @var list<class-string<EventHandler\AfterFunctionLikeAnalysisInterface>>
     */
    public $after_functionlike_checks = [];
    /** @var list<class-string<Hook\AfterFunctionLikeAnalysisInterface>> */
    public $legacy_after_functionlike_checks = [];

    /**
     * Static methods to be called to see if taints should be added
     *
     * @var list<class-string<EventHandler\AddTaintsInterface>>
     */
    public $add_taints_checks = [];

    /**
     * Static methods to be called to see if taints should be removed
     *
     * @var list<class-string<EventHandler\RemoveTaintsInterface>>
     */
    public $remove_taints_checks = [];

    /**
     * @param class-string $class
     */
    public function registerClass(string $class): void
    {
        if (is_subclass_of($class, Hook\AfterMethodCallAnalysisInterface::class)) {
            $this->legacy_after_method_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterMethodCallAnalysisInterface::class)) {
            $this->after_method_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterFunctionCallAnalysisInterface::class)) {
            $this->legacy_after_function_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterFunctionCallAnalysisInterface::class)) {
            $this->after_function_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterEveryFunctionCallAnalysisInterface::class)) {
            $this->legacy_after_every_function_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterEveryFunctionCallAnalysisInterface::class)) {
            $this->after_every_function_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterExpressionAnalysisInterface::class)) {
            $this->legacy_after_expression_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterExpressionAnalysisInterface::class)) {
            $this->after_expression_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterStatementAnalysisInterface::class)) {
            $this->legacy_after_statement_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterStatementAnalysisInterface::class)) {
            $this->after_statement_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\StringInterpreterInterface::class)) {
            $this->legacy_string_interpreters[] = $class;
        } elseif (is_subclass_of($class, EventHandler\StringInterpreterInterface::class)) {
            $this->string_interpreters[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterClassLikeExistenceCheckInterface::class)) {
            $this->legacy_after_classlike_exists_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterClassLikeExistenceCheckInterface::class)) {
            $this->after_classlike_exists_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterClassLikeAnalysisInterface::class)) {
            $this->legacy_after_classlike_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterClassLikeAnalysisInterface::class)) {
            $this->after_classlike_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterClassLikeVisitInterface::class)) {
            $this->legacy_after_visit_classlikes[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterClassLikeVisitInterface::class)) {
            $this->after_visit_classlikes[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterCodebasePopulatedInterface::class)) {
            $this->legacy_after_codebase_populated[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterCodebasePopulatedInterface::class)) {
            $this->after_codebase_populated[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterAnalysisInterface::class)) {
            $this->legacy_after_analysis[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterAnalysisInterface::class)) {
            $this->after_analysis[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterFileAnalysisInterface::class)) {
            $this->legacy_after_file_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterFileAnalysisInterface::class)) {
            $this->after_file_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\BeforeFileAnalysisInterface::class)) {
            $this->legacy_before_file_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\BeforeFileAnalysisInterface::class)) {
            $this->before_file_checks[] = $class;
        }

        if (is_subclass_of($class, Hook\AfterFunctionLikeAnalysisInterface::class)) {
            $this->legacy_after_functionlike_checks[] = $class;
        } elseif (is_subclass_of($class, EventHandler\AfterFunctionLikeAnalysisInterface::class)) {
            $this->after_functionlike_checks[] = $class;
        }

        if (is_subclass_of($class, EventHandler\AddTaintsInterface::class)) {
            $this->add_taints_checks[] = $class;
        }

        if (is_subclass_of($class, EventHandler\RemoveTaintsInterface::class)) {
            $this->remove_taints_checks[] = $class;
        }
    }

    public function hasAfterMethodCallAnalysisHandlers(): bool
    {
        return count($this->after_method_checks) || count($this->legacy_after_method_checks);
    }

    public function dispatchAfterMethodCallAnalysis(Event\AfterMethodCallAnalysisEvent $event): void
    {
        foreach ($this->after_method_checks as $handler) {
            $handler::afterMethodCallAnalysis($event);
        }

        foreach ($this->legacy_after_method_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $return_type_candidate = $event->getReturnTypeCandidate();
            $handler::afterMethodCallAnalysis(
                $event->getExpr(),
                $event->getMethodId(),
                $event->getAppearingMethodId(),
                $event->getDeclaringMethodId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements,
                $return_type_candidate
            );
            $event->setFileReplacements($file_replacements);
            $event->setReturnTypeCandidate($return_type_candidate);
        }
    }

    public function dispatchAfterFunctionCallAnalysis(Event\AfterFunctionCallAnalysisEvent $event): void
    {
        foreach ($this->after_function_checks as $handler) {
            $handler::afterFunctionCallAnalysis($event);
        }

        foreach ($this->legacy_after_function_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterFunctionCallAnalysis(
                $event->getExpr(),
                $event->getFunctionId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $event->getReturnTypeCandidate(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterEveryFunctionCallAnalysis(Event\AfterEveryFunctionCallAnalysisEvent $event): void
    {
        foreach ($this->after_every_function_checks as $handler) {
            $handler::afterEveryFunctionCallAnalysis($event);
        }

        foreach ($this->legacy_after_every_function_checks as $handler) {
            $handler::afterEveryFunctionCallAnalysis(
                $event->getExpr(),
                $event->getFunctionId(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterExpressionAnalysis(Event\AfterExpressionAnalysisEvent $event): ?bool
    {
        foreach ($this->after_expression_checks as $handler) {
            if ($handler::afterExpressionAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_expression_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterExpressionAnalysis(
                $event->getExpr(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function dispatchAfterStatementAnalysis(Event\AfterStatementAnalysisEvent $event): ?bool
    {
        foreach ($this->after_statement_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_statement_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getContext(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function dispatchStringInterpreter(Event\StringInterpreterEvent $event): ?TLiteralString
    {
        foreach ($this->string_interpreters as $handler) {
            if ($type = $handler::getTypeFromValue($event)) {
                return $type;
            }
        }

        foreach ($this->legacy_string_interpreters as $handler) {
            if ($type = $handler::getTypeFromValue($event->getValue())) {
                return $type;
            }
        }

        return null;
    }

    public function dispatchAfterClassLikeExistenceCheck(Event\AfterClassLikeExistenceCheckEvent $event): void
    {
        foreach ($this->after_classlike_exists_checks as $handler) {
            $handler::afterClassLikeExistenceCheck($event);
        }

        foreach ($this->legacy_after_classlike_exists_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterClassLikeExistenceCheck(
                $event->getFqClassName(),
                $event->getCodeLocation(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterClassLikeAnalysis(Event\AfterClassLikeAnalysisEvent $event): ?bool
    {
        foreach ($this->after_classlike_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_classlike_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getClasslikeStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    public function hasAfterClassLikeVisitHandlers(): bool
    {
        return count($this->after_visit_classlikes) || count($this->legacy_after_visit_classlikes);
    }

    public function dispatchAfterClassLikeVisit(Event\AfterClassLikeVisitEvent $event): void
    {
        foreach ($this->after_visit_classlikes as $handler) {
            $handler::afterClassLikeVisit($event);
        }

        foreach ($this->legacy_after_visit_classlikes as $handler) {
            $file_replacements = $event->getFileReplacements();
            $handler::afterClassLikeVisit(
                $event->getStmt(),
                $event->getStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            );
            $event->setFileReplacements($file_replacements);
        }
    }

    public function dispatchAfterCodebasePopulated(Event\AfterCodebasePopulatedEvent $event): void
    {
        foreach ($this->after_codebase_populated as $handler) {
            $handler::afterCodebasePopulated($event);
        }

        foreach ($this->legacy_after_codebase_populated as $handler) {
            $handler::afterCodebasePopulated(
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterAnalysis(Event\AfterAnalysisEvent $event): void
    {
        foreach ($this->after_analysis as $handler) {
            $handler::afterAnalysis($event);
        }

        foreach ($this->legacy_after_analysis as $handler) {
            /** @psalm-suppress MixedArgumentTypeCoercion due to Psalm bug */
            $handler::afterAnalysis(
                $event->getCodebase(),
                $event->getIssues(),
                $event->getBuildInfo(),
                $event->getSourceControlInfo()
            );
        }
    }

    public function dispatchAfterFileAnalysis(Event\AfterFileAnalysisEvent $event): void
    {
        foreach ($this->after_file_checks as $handler) {
            $handler::afterAnalyzeFile($event);
        }

        foreach ($this->legacy_after_file_checks as $handler) {
            $handler::afterAnalyzeFile(
                $event->getStatementsSource(),
                $event->getFileContext(),
                $event->getFileStorage(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchBeforeFileAnalysis(Event\BeforeFileAnalysisEvent $event): void
    {
        foreach ($this->before_file_checks as $handler) {
            $handler::beforeAnalyzeFile($event);
        }

        foreach ($this->legacy_before_file_checks as $handler) {
            $handler::beforeAnalyzeFile(
                $event->getStatementsSource(),
                $event->getFileContext(),
                $event->getFileStorage(),
                $event->getCodebase()
            );
        }
    }

    public function dispatchAfterFunctionLikeAnalysis(Event\AfterFunctionLikeAnalysisEvent $event): ?bool
    {
        foreach ($this->after_functionlike_checks as $handler) {
            if ($handler::afterStatementAnalysis($event) === false) {
                return false;
            }
        }

        foreach ($this->legacy_after_functionlike_checks as $handler) {
            $file_replacements = $event->getFileReplacements();
            if ($handler::afterStatementAnalysis(
                $event->getStmt(),
                $event->getClasslikeStorage(),
                $event->getStatementsSource(),
                $event->getCodebase(),
                $file_replacements
            ) === false) {
                return false;
            }
            $event->setFileReplacements($file_replacements);
        }

        return null;
    }

    /**
     * @return list<string>
     */
    public function dispatchAddTaints(Event\AddRemoveTaintsEvent $event): array
    {
        $added_taints = [];

        foreach ($this->add_taints_checks as $handler) {
            $added_taints = \array_merge($added_taints, $handler::addTaints($event));
        }

        return $added_taints;
    }

    /**
     * @return list<string>
     */
    public function dispatchRemoveTaints(Event\AddRemoveTaintsEvent $event): array
    {
        $removed_taints = [];

        foreach ($this->remove_taints_checks as $handler) {
            $removed_taints = \array_merge($removed_taints, $handler::removeTaints($event));
        }

        return $removed_taints;
    }
}
