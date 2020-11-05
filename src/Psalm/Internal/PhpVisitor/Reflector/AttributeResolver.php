<?php
namespace Psalm\Internal\PhpVisitor\Reflector;

use PhpParser;
use Psalm\Codebase;
use Psalm\CodeLocation;
use Psalm\Aliases;
use Psalm\Internal\Analyzer\ClassLikeAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\SimpleTypeInferer;
use Psalm\Internal\Scanner\FileScanner;
use Psalm\Storage\AttributeStorage;
use Psalm\Storage\FileStorage;
use Psalm\Type;
use function strtolower;

class AttributeResolver
{
    public static function resolve(
        Codebase $codebase,
        FileScanner $file_scanner,
        FileStorage $file_storage,
        Aliases $aliases,
        PhpParser\Node\Attribute $stmt,
        ?string $fq_classlike_name
    ) : AttributeStorage {
        if ($stmt->name instanceof PhpParser\Node\Name\FullyQualified) {
            $fq_type_string = (string)$stmt->name;

            $codebase->scanner->queueClassLikeForScanning($fq_type_string);
            $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
        } else {
            $fq_type_string = ClassLikeAnalyzer::getFQCLNFromNameObject($stmt->name, $aliases);

            $codebase->scanner->queueClassLikeForScanning($fq_type_string);
            $file_storage->referenced_classlikes[strtolower($fq_type_string)] = $fq_type_string;
        }

        $args = [];

        foreach ($stmt->args as $arg_node) {
            $key = null;

            if ($arg_node->name) {
                $key = $arg_node->name->name;
            }

            $const_type = SimpleTypeInferer::infer(
                $codebase,
                new \Psalm\Internal\Provider\NodeDataProvider(),
                $arg_node->value,
                $aliases,
                null,
                [],
                $fq_classlike_name
            );

            if (!$const_type) {
                $const_type = ExpressionResolver::getUnresolvedClassConstExpr(
                    $arg_node->value,
                    $aliases,
                    $fq_classlike_name
                );
            }

            if (!$const_type) {
                $const_type = Type::getMixed();
            }

            $args[] = new \Psalm\Storage\AttributeArg(
                $key,
                $const_type,
                new CodeLocation($file_scanner, $arg_node->value)
            );
        }

        return new AttributeStorage(
            $fq_type_string,
            $args,
            new CodeLocation($file_scanner, $stmt),
            new CodeLocation($file_scanner, $stmt->name)
        );
    }
}
