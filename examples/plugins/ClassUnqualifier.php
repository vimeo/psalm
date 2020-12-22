<?php
namespace Psalm\Example\Plugin;

use Psalm\FileManipulation;
use Psalm\Plugin\Hook\AfterClassLikeExistenceCheckInterface;
use Psalm\Plugin\Hook\Event\AfterClassLikeExistenceCheckEvent;
use function strtolower;
use function implode;
use function array_map;

class ClassUnqualifier implements AfterClassLikeExistenceCheckInterface
{
    public static function afterClassLikeExistenceCheck(
        AfterClassLikeExistenceCheckEvent $event
    ): void {
        $fq_class_name = $event->getFqClassName();
        $code_location = $event->getCodeLocation();
        $statements_source = $event->getStatementsSource();
        $file_replacements = $event->getFileReplacements();

        $candidate_type = $code_location->getSelectedText();
        $aliases = $statements_source->getAliasedClassesFlipped();

        if ($statements_source->getFilePath() !== $code_location->file_path) {
            return;
        }

        if (\strpos($candidate_type, '\\' . $fq_class_name) !== false) {
            $type_tokens = \Psalm\Internal\Type\TypeTokenizer::tokenize($candidate_type, false);

            foreach ($type_tokens as &$type_token) {
                if ($type_token[0] === ('\\' . $fq_class_name)
                    && isset($aliases[strtolower($fq_class_name)])
                ) {
                    $type_token[0] = $aliases[strtolower($fq_class_name)];
                }
            }

            $new_candidate_type = implode(
                '',
                array_map(
                    function ($f) {
                        return $f[0];
                    },
                    $type_tokens
                )
            );

            if ($new_candidate_type !== $candidate_type) {
                $bounds = $code_location->getSelectionBounds();
                $file_replacements[] = new FileManipulation($bounds[0], $bounds[1], $new_candidate_type);
            }
            $event->setFileReplacements($file_replacements);
        }
    }
}
