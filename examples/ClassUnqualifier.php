<?php
namespace Psalm\Example\Plugin;

use PhpParser;
use Psalm\Checker;
use Psalm\Context;
use Psalm\CodeLocation;
use Psalm\FileManipulation\FileManipulation;
use Psalm\StatementsSource;
use Psalm\Type;

class ClassUnqualifier extends \Psalm\Plugin
{
    /**
     * @param  string             $fq_class_name
     * @param  FileManipulation[] $file_replacements
     *
     * @return void
     */
    public function afterClassLikeExistsCheck(
        StatementsSource $statements_source,
        $fq_class_name,
        CodeLocation $code_location,
        array &$file_replacements = []
    ) {
        $candidate_type = $code_location->getSelectedText();
        $aliases = $statements_source->getAliasedClassesFlipped();

        if ($statements_source->getFileChecker()->getFilePath() !== $code_location->file_path) {
            return;
        }

        if (strpos($candidate_type, '\\' . $fq_class_name) !== false) {
            $type_tokens = Type::tokenize($candidate_type, false);

            foreach ($type_tokens as &$type_token) {
                if ($type_token === ('\\' . $fq_class_name)
                    && isset($aliases[strtolower($fq_class_name)])
                ) {
                    $type_token = $aliases[strtolower($fq_class_name)];
                }
            }

            $new_candidate_type = implode('', $type_tokens);

            if ($new_candidate_type !== $candidate_type) {
                $bounds = $code_location->getSelectionBounds();
                $file_replacements[] = new FileManipulation($bounds[0], $bounds[1], $new_candidate_type);
            }
        }
    }
}

return new ClassUnqualifier;
