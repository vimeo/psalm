<?php

declare(strict_types=1);

namespace Psalm\Internal\LanguageServer;

use Exception;
use InvalidArgumentException;
use LanguageServerProtocol\Command;
use LanguageServerProtocol\CompletionItem;
use LanguageServerProtocol\CompletionItemKind;
use LanguageServerProtocol\InsertTextFormat;
use LanguageServerProtocol\ParameterInformation;
use LanguageServerProtocol\Position;
use LanguageServerProtocol\Range;
use LanguageServerProtocol\SignatureInformation;
use LanguageServerProtocol\TextEdit;
use Psalm\CodeLocation;
use Psalm\CodeLocation\Raw;
use Psalm\Codebase as PsalmCodebase;
use Psalm\Exception\UnanalyzedFileException;
use Psalm\Internal\Analyzer\NamespaceAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\ConstFetchAnalyzer;
use Psalm\Internal\Analyzer\Statements\Expression\Fetch\VariableFetchAnalyzer;
use Psalm\Internal\Codebase\InternalCallMapHandler;
use Psalm\Internal\MethodIdentifier;
use Psalm\Type;
use Psalm\Type\Atomic\TBool;
use Psalm\Type\Atomic\TClassConstant;
use Psalm\Type\Atomic\TKeyedArray;
use Psalm\Type\Atomic\TLiteralInt;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TNamedObject;
use Psalm\Type\Union;
use ReflectionProperty;
use UnexpectedValueException;

use function array_pop;
use function array_reverse;
use function count;
use function dirname;
use function error_log;
use function explode;
use function implode;
use function is_numeric;
use function krsort;
use function ksort;
use function preg_match;
use function preg_replace;
use function strlen;
use function strpos;
use function strrpos;
use function strtolower;
use function substr;
use function substr_count;

/**
 * @internal
 */
class Codebase extends PsalmCodebase
{

    /**
     * Get Reference from Position
     */
    public function getReferenceAtPosition(string $file_path, Position $position): ?Reference
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map] = $this->analyzer->getMapsForFile($file_path);

        $symbol = null;

        if (!$reference_map && !$type_map) {
            return null;
        }

        $reference_start_pos = null;
        $reference_end_pos = null;

        ksort($reference_map);

        foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }
            $reference_start_pos = $start_pos;
            $reference_end_pos = $end_pos;
            $symbol = $possible_reference;
        }

        if ($symbol === null || $reference_start_pos === null || $reference_end_pos === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($reference_start_pos, $file_contents),
            self::getPositionFromOffset($reference_end_pos, $file_contents)
        );

        return new Reference($file_path, $symbol, $range);
    }

    /**
     * Get Markup content from Reference
     *
     * @param Reference $reference
     */
    public function getMarkupContentForSymbol(Reference $reference): ?PHPMarkdownContent
    {
        //Direct Assignment
        if (is_numeric($reference->symbol[0])) {
            return new PHPMarkdownContent(
                preg_replace('/^[^:]*:/', '', $reference->symbol)
            );
        }

        //Class
        if (strpos($reference->symbol, '::')) {
            //Class Method
            if (strpos($reference->symbol, '()')) {
                $symbol = substr($reference->symbol, 0, -2);

                /** @psalm-suppress ArgumentTypeCoercion */
                $method_id = new MethodIdentifier(...explode('::', $symbol));

                $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

                if (!$declaring_method_id) {
                    return null;
                }

                $storage = $this->methods->getStorage($declaring_method_id);

                return new PHPMarkdownContent(
                    $storage->getHoverMarkdown(),
                    "{$storage->defining_fqcln}::{$storage->cased_name}",
                    $storage->description
                );
            }

            [, $symbol_name] = explode('::', $reference->symbol);

            //Class Property
            if (strpos($reference->symbol, '$') !== false) {
                $storage = $this->properties->getStorage($reference->symbol);

                return new PHPMarkdownContent(
                    "{$storage->getInfo()} {$symbol_name}",
                    $reference->symbol,
                    $storage->description
                );
            }

            [$fq_classlike_name, $const_name] = explode('::', $reference->symbol);

            $class_constants = $this->classlikes->getConstantsForClass(
                $fq_classlike_name,
                ReflectionProperty::IS_PRIVATE
            );

            if (!isset($class_constants[$const_name])) {
                return null;
            }

            //Class Constant
            return new PHPMarkdownContent(
                $class_constants[$const_name]->getHoverMarkdown($const_name),
                $fq_classlike_name.'::'.$const_name,
                $class_constants[$const_name]->description
            );
        }

        //Procedural Function
        if (strpos($reference->symbol, '()')) {
            $function_id = strtolower(substr($reference->symbol, 0, -2));
            $file_storage = $this->file_storage_provider->get($reference->file_path);

            if (isset($file_storage->functions[$function_id])) {
                $function_storage = $file_storage->functions[$function_id];

                return new PHPMarkdownContent(
                    $function_storage->getHoverMarkdown(),
                    $function_id,
                    $function_storage->description
                );
            }

            if (!$function_id) {
                return null;
            }

            $function = $this->functions->getStorage(null, $function_id);

            return new PHPMarkdownContent(
                $function->getHoverMarkdown(),
                $function_id,
                $function->description
            );
        }

        //Procedural Variable
        if (strpos($reference->symbol, '$') === 0) {
            $type = VariableFetchAnalyzer::getGlobalType($reference->symbol);
            if (!$type->isMixed()) {
                return new PHPMarkdownContent(
                    (string) $type,
                    $reference->symbol
                );
            }
        }

        try {
            $storage = $this->classlike_storage_provider->get($reference->symbol);
            return new PHPMarkdownContent(
                ($storage->abstract ? 'abstract ' : '') . 'class ' . $storage->name,
                $storage->name,
                $storage->description
            );
        } catch (InvalidArgumentException $e) {
            //continue on as normal
        }

        if (strpos($reference->symbol, '\\')) {
            $const_name_parts = explode('\\', $reference->symbol);
            $const_name = array_pop($const_name_parts);
            $namespace_name = implode('\\', $const_name_parts);

            $namespace_constants = NamespaceAnalyzer::getConstantsForNamespace(
                $namespace_name,
                ReflectionProperty::IS_PUBLIC
            );
            //Namespace Constant
            if (isset($namespace_constants[$const_name])) {
                $type = $namespace_constants[$const_name];
                return new PHPMarkdownContent(
                    $reference->symbol . ' ' . $type,
                    $reference->symbol
                );
            }
        } else {
            $file_storage = $this->file_storage_provider->get($reference->file_path);
            // ?
            if (isset($file_storage->constants[$reference->symbol])) {
                return new PHPMarkdownContent(
                    'const ' . $reference->symbol . ' ' . $file_storage->constants[$reference->symbol],
                    $reference->symbol
                );
            }
            $type = ConstFetchAnalyzer::getGlobalConstType($this, $reference->symbol, $reference->symbol);

            //Global Constant
            if ($type) {
                return new PHPMarkdownContent(
                    'const ' . $reference->symbol . ' ' . $type,
                    $reference->symbol
                );
            }
        }
        return null;
    }

    private static function getPositionFromOffset(int $offset, string $file_contents): Position
    {
        $file_contents = substr($file_contents, 0, $offset);

        $offsetLength = $offset - strlen($file_contents);

        //PHP 8.0: Argument #3 ($offset) must be contained in argument #1 ($haystack)
        if (($textlen = strlen($file_contents)) < $offsetLength) {
            $offsetLength = $textlen;
        }

        $before_newline_count = strrpos($file_contents, "\n", $offsetLength);

        return new Position(
            substr_count($file_contents, "\n"),
            $offset - (int)$before_newline_count - 1
        );
    }

    /**
     * @return array{0: string, 1: '->'|'::'|'['|'symbol', 2: int}|null
     */
    public function getCompletionDataAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map] = $this->analyzer->getMapsForFile($file_path);

        if (!$reference_map && !$type_map) {
            return null;
        }

        krsort($type_map);

        foreach ($type_map as $start_pos => [$end_pos_excluding_whitespace, $possible_type]) {
            if ($offset < $start_pos) {
                continue;
            }

            /** @psalm-suppress PossiblyUndefinedIntArrayOffset */
            $num_whitespace_bytes = preg_match('/\G\s+/', $file_contents, $matches, 0, $end_pos_excluding_whitespace)
                ? strlen($matches[0])
                : 0;
            $end_pos = $end_pos_excluding_whitespace + $num_whitespace_bytes;

            if ($offset - $end_pos === 1) {
                $candidate_gap = substr($file_contents, $end_pos, 1);

                if ($candidate_gap === '[') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    if ($recent_type === 'mixed') {
                        return null;
                    }

                    return [$recent_type, $gap, $offset];
                }
            }

            if ($offset - $end_pos === 2 || $offset - $end_pos === 3) {
                $candidate_gap = substr($file_contents, $end_pos, 2);

                if ($candidate_gap === '->' || $candidate_gap === '::') {
                    $gap = $candidate_gap;
                    $recent_type = $possible_type;

                    if ($recent_type === 'mixed') {
                        return null;
                    }

                    return [$recent_type, $gap, $offset];
                }
            }
        }

        foreach ($reference_map as $start_pos => [$end_pos, $possible_reference]) {
            if ($offset < $start_pos) {
                continue;
            }
            // If the reference precedes a "::" then treat it as a class reference.
            if ($offset - $end_pos === 2 && substr($file_contents, $end_pos, 2) === '::') {
                return [$possible_reference, '::', $offset];
            }

            // Only continue for references that are partial / don't exist.
            if ($possible_reference[0] !== '*') {
                continue;
            }

            if ($offset - $end_pos === 0) {
                $recent_type = $possible_reference;

                return [$recent_type, 'symbol', $offset];
            }
        }

        return null;
    }

    public function getTypeContextAtPosition(string $file_path, Position $position): ?Union
    {
        $file_contents = $this->getFileContents($file_path);
        $offset = $position->toOffset($file_contents);

        [$reference_map, $type_map, $argument_map] = $this->analyzer->getMapsForFile($file_path);
        if (!$reference_map && !$type_map && !$argument_map) {
            return null;
        }
        foreach ($argument_map as $start_pos => [$end_pos, $function, $argument_num]) {
            if ($offset < $start_pos || $offset > $end_pos) {
                continue;
            }
            // First parameter to a function-like
            $function_storage = $this->getFunctionStorageForSymbol($file_path, $function . '()');
            if (!$function_storage || !$function_storage->params || !isset($function_storage->params[$argument_num])) {
                return null;
            }

            return $function_storage->params[$argument_num]->type;
        }

        return null;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForClassishThing(
        string $type_string,
        string $gap,
        bool $snippets_supported = false
    ): array {
        $completion_items = [];

        $type = Type::parseString($type_string);

        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TNamedObject) {
                try {
                    $class_storage = $this->classlike_storage_provider->get($atomic_type->value);

                    foreach ($class_storage->appearing_method_ids as $declaring_method_id) {
                        $method_storage = $this->methods->getStorage($declaring_method_id);

                        if ($method_storage->is_static || $gap === '->') {
                            $completion_item = new CompletionItem(
                                $method_storage->cased_name,
                                CompletionItemKind::METHOD,
                                $method_storage->getCompletionSignature(),
                                $method_storage->description,
                                (string)$method_storage->visibility,
                                $method_storage->cased_name,
                                $method_storage->cased_name,
                                null,
                                null,
                                new Command('Trigger parameter hints', 'editor.action.triggerParameterHints'),
                                null,
                                2
                            );

                            if ($snippets_supported && count($method_storage->params) > 0) {
                                $completion_item->insertText .= '($0)';
                                $completion_item->insertTextFormat = InsertTextFormat::SNIPPET;
                            } else {
                                $completion_item->insertText .= '()';
                            }

                            $completion_items[] = $completion_item;
                        }
                    }

                    foreach ($class_storage->declaring_property_ids as $property_name => $declaring_class) {
                        $property_storage = $this->properties->getStorage(
                            $declaring_class . '::$' . $property_name
                        );

                        if ($property_storage->is_static || $gap === '->') {
                            $completion_items[] = new CompletionItem(
                                '$' . $property_name,
                                CompletionItemKind::PROPERTY,
                                $property_storage->getInfo(),
                                $property_storage->description,
                                (string)$property_storage->visibility,
                                $property_name,
                                ($gap === '::' ? '$' : '') . $property_name
                            );
                        }
                    }

                    foreach ($class_storage->constants as $const_name => $const) {
                        $completion_items[] = new CompletionItem(
                            $const_name,
                            CompletionItemKind::VARIABLE,
                            'const ' . $const_name,
                            $const->description,
                            null,
                            $const_name,
                            $const_name
                        );
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                    continue;
                }
            }
        }

        return $completion_items;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForArrayKeys(
        string $type_string
    ): array {
        $completion_items = [];
        $type = Type::parseString($type_string);
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TKeyedArray) {
                foreach ($atomic_type->properties as $property_name => $property) {
                    $completion_items[] = new CompletionItem(
                        (string) $property_name,
                        CompletionItemKind::PROPERTY,
                        (string) $property,
                        null,
                        null,
                        null,
                        "'$property_name'"
                    );
                }
            }
        }
        return $completion_items;
    }

    /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForPartialSymbol(
        string $type_string,
        int $offset,
        string $file_path
    ): array {
        $fq_suggestion = false;

        if (($type_string[1] ?? '') === '\\') {
            $fq_suggestion = true;
        }

        $matching_classlike_names = $this->classlikes->getMatchingClassLikeNames($type_string);

        $completion_items = [];

        $file_storage = $this->file_storage_provider->get($file_path);

        $aliases = null;

        foreach ($file_storage->classlikes_in_file as $fq_class_name => $_) {
            try {
                $class_storage = $this->classlike_storage_provider->get($fq_class_name);
            } catch (Exception $e) {
                continue;
            }

            if (!$class_storage->stmt_location) {
                continue;
            }

            if ($offset > $class_storage->stmt_location->raw_file_start
                && $offset < $class_storage->stmt_location->raw_file_end
            ) {
                $aliases = $class_storage->aliases;
                break;
            }
        }

        if (!$aliases) {
            foreach ($file_storage->namespace_aliases as $namespace_start => $namespace_aliases) {
                if ($namespace_start < $offset) {
                    $aliases = $namespace_aliases;
                    break;
                }
            }

            if (!$aliases) {
                $aliases = $file_storage->aliases;
            }
        }

        foreach ($matching_classlike_names as $fq_class_name) {
            $extra_edits = [];

            $insertion_text = Type::getStringFromFQCLN(
                $fq_class_name,
                $aliases && $aliases->namespace ? $aliases->namespace : null,
                $aliases->uses_flipped ?? [],
                null
            );

            if ($aliases
                && !$fq_suggestion
                && $aliases->namespace
                && $insertion_text === '\\' . $fq_class_name
                && $aliases->namespace_first_stmt_start
            ) {
                $file_contents = $this->getFileContents($file_path);

                $class_name = preg_replace('/^.*\\\/', '', $fq_class_name);

                if ($aliases->uses_end) {
                    $position = self::getPositionFromOffset($aliases->uses_end, $file_contents);
                    $extra_edits[] = new TextEdit(
                        new Range(
                            $position,
                            $position
                        ),
                        "\n" . 'use ' . $fq_class_name . ';'
                    );
                } else {
                    $position = self::getPositionFromOffset($aliases->namespace_first_stmt_start, $file_contents);
                    $extra_edits[] = new TextEdit(
                        new Range(
                            $position,
                            $position
                        ),
                        'use ' . $fq_class_name . ';' . "\n" . "\n"
                    );
                }

                $insertion_text = $class_name;
            }

            try {
                $class_storage = $this->classlike_storage_provider->get($fq_class_name);
                $description = $class_storage->description;
            } catch (Exception $e) {
                $description = null;
            }

            $completion_items[] = new CompletionItem(
                $fq_class_name,
                CompletionItemKind::CLASS_,
                null,
                $description,
                null,
                $fq_class_name,
                $insertion_text,
                null,
                $extra_edits
            );
        }

        $functions = $this->functions->getMatchingFunctionNames($type_string, $offset, $file_path, $this);

        $namespace_map = [];
        if ($aliases) {
            $namespace_map += $aliases->uses_flipped;
            if ($aliases->namespace) {
                $namespace_map[$aliases->namespace] = '';
            }
        }

        // Sort the map by longest first, so we replace most specific
        // used namespaces first.
        ksort($namespace_map);
        $namespace_map = array_reverse($namespace_map);

        foreach ($functions as $function_lowercase => $function) {
            // Transform FQFN relative to all uses namespaces
            $function_name = $function->cased_name;
            if (!$function_name) {
                continue;
            }
            $in_namespace_map = false;
            foreach ($namespace_map as $namespace_name => $namespace_alias) {
                if (strpos($function_lowercase, $namespace_name . '\\') === 0) {
                    $function_name = $namespace_alias . '\\' . substr($function_name, strlen($namespace_name) + 1);
                    $in_namespace_map = true;
                }
            }
            // If the function is not use'd, and it's not a global function
            // prepend it with a backslash.
            if (!$in_namespace_map && strpos($function_name, '\\') !== false) {
                $function_name = '\\' . $function_name;
            }
            $completion_items[] = new CompletionItem(
                $function_name,
                CompletionItemKind::FUNCTION,
                $function->getCompletionSignature(),
                $function->description,
                null,
                $function_name,
                $function_name . (count($function->params) !== 0 ? '($0)' : '()'),
                null,
                null,
                new Command('Trigger parameter hints', 'editor.action.triggerParameterHints'),
                null,
                2
            );
        }

        return $completion_items;
    }

   /**
     * @return list<CompletionItem>
     */
    public function getCompletionItemsForType(Union $type): array
    {
        $completion_items = [];
        foreach ($type->getAtomicTypes() as $atomic_type) {
            if ($atomic_type instanceof TBool) {
                $bools = (string) $atomic_type === 'bool' ? ['true', 'false'] : [(string) $atomic_type];
                foreach ($bools as $property_name) {
                    $completion_items[] = new CompletionItem(
                        $property_name,
                        CompletionItemKind::VALUE,
                        'bool',
                        null,
                        null,
                        null,
                        $property_name
                    );
                }
            } elseif ($atomic_type instanceof TLiteralString) {
                $completion_items[] = new CompletionItem(
                    $atomic_type->value,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    "'$atomic_type->value'"
                );
            } elseif ($atomic_type instanceof TLiteralInt) {
                $completion_items[] = new CompletionItem(
                    (string) $atomic_type->value,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    (string) $atomic_type->value
                );
            } elseif ($atomic_type instanceof TClassConstant) {
                $const = $atomic_type->fq_classlike_name . '::' . $atomic_type->const_name;
                $completion_items[] = new CompletionItem(
                    $const,
                    CompletionItemKind::VALUE,
                    $atomic_type->getId(),
                    null,
                    null,
                    null,
                    $const
                );
            }
        }
        return $completion_items;
    }

    /**
     * @return array{0: non-empty-string, 1: int, 2: Range}|null
     */
    public function getFunctionArgumentAtPosition(string $file_path, Position $position): ?array
    {
        $is_open = $this->file_provider->isOpen($file_path);

        if (!$is_open) {
            throw new UnanalyzedFileException($file_path . ' is not open');
        }

        $file_contents = $this->getFileContents($file_path);

        $offset = $position->toOffset($file_contents);

        [, , $argument_map] = $this->analyzer->getMapsForFile($file_path);

        $reference = null;
        $argument_number = null;

        if (!$argument_map) {
            return null;
        }

        $start_pos = null;
        $end_pos = null;

        ksort($argument_map);

        foreach ($argument_map as $start_pos => [$end_pos, $possible_reference, $possible_argument_number]) {
            if ($offset < $start_pos) {
                break;
            }

            if ($offset > $end_pos) {
                continue;
            }

            $reference = $possible_reference;
            $argument_number = $possible_argument_number;
        }

        if ($reference === null || $start_pos === null || $end_pos === null || $argument_number === null) {
            return null;
        }

        $range = new Range(
            self::getPositionFromOffset($start_pos, $file_contents),
            self::getPositionFromOffset($end_pos, $file_contents)
        );

        return [$reference, $argument_number, $range];
    }

    /**
     * @param  non-empty-string $function_symbol
     */
    public function getSignatureInformation(
        string $function_symbol,
        string $file_path = null
    ): ?SignatureInformation {
        $signature_label = '';
        $signature_documentation = null;
        if (strpos($function_symbol, '::') !== false) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $method_id = new MethodIdentifier(...explode('::', $function_symbol));

            $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

            if ($declaring_method_id === null) {
                return null;
            }

            $method_storage = $this->methods->getStorage($declaring_method_id);
            $params = $method_storage->params;
            $signature_label = $method_storage->cased_name;
            $signature_documentation = $method_storage->description;
        } else {
            try {
                if ($file_path) {
                    $function_storage = $this->functions->getStorage(
                        null,
                        strtolower($function_symbol),
                        dirname($file_path),
                        $file_path
                    );
                } else {
                    $function_storage = $this->functions->getStorage(null, strtolower($function_symbol));
                }
                $params = $function_storage->params;
                $signature_label = $function_storage->cased_name;
                $signature_documentation = $function_storage->description;
            } catch (Exception $exception) {
                if (InternalCallMapHandler::inCallMap($function_symbol)) {
                    $callables = InternalCallMapHandler::getCallablesFromCallMap($function_symbol);

                    if (!$callables || !$callables[0]->params) {
                        throw $exception;
                    }

                    $params = $callables[0]->params;
                } else {
                    throw $exception;
                }
            }
        }

        $signature_label .= '(';
        $parameters = [];

        foreach ($params as $i => $param) {
            $parameter_label = ($param->type ?: 'mixed') . ' $' . $param->name;
            $parameters[] = new ParameterInformation(
                [
                    strlen($signature_label),
                    strlen($signature_label) + strlen($parameter_label),
                ],
                $param->description ?? null
            );

            $signature_label .= $parameter_label;

            if ($i < (count($params) - 1)) {
                $signature_label .= ', ';
            }
        }

        $signature_label .= ')';

        return new SignatureInformation(
            $signature_label,
            $parameters,
            $signature_documentation
        );
    }

    public function getSymbolLocation(Reference $reference): ?CodeLocation
    {
        if (is_numeric($reference->symbol[0])) {
            $symbol = preg_replace('/:.*/', '', $reference->symbol);
            $symbol_parts = explode('-', $symbol);

            if (!isset($symbol_parts[0]) || !isset($symbol_parts[1])) {
                return null;
            }

            $file_contents = $this->getFileContents($reference->file_path);

            return new Raw(
                $file_contents,
                $reference->file_path,
                $this->config->shortenFileName($reference->file_path),
                (int) $symbol_parts[0],
                (int) $symbol_parts[1]
            );
        }

        try {
            if (strpos($reference->symbol, '::')) {
                if (strpos($reference->symbol, '()')) {
                    $symbol = substr($reference->symbol, 0, -2);

                    /** @psalm-suppress ArgumentTypeCoercion */
                    $method_id = new MethodIdentifier(...explode('::', $symbol));

                    $declaring_method_id = $this->methods->getDeclaringMethodId($method_id);

                    if (!$declaring_method_id) {
                        return null;
                    }

                    $storage = $this->methods->getStorage($declaring_method_id);

                    return $storage->location;
                }

                if (strpos($reference->symbol, '$') !== false) {
                    $storage = $this->properties->getStorage($reference->symbol);

                    return $storage->location;
                }

                [$fq_classlike_name, $const_name] = explode('::', $reference->symbol);

                $class_constants = $this->classlikes->getConstantsForClass(
                    $fq_classlike_name,
                    ReflectionProperty::IS_PRIVATE
                );

                if (!isset($class_constants[$const_name])) {
                    return null;
                }

                return $class_constants[$const_name]->location;
            }

            if (strpos($reference->symbol, '()')) {
                $file_storage = $this->file_storage_provider->get($reference->file_path);

                $function_id = strtolower(substr($reference->symbol, 0, -2));

                if (isset($file_storage->functions[$function_id])) {
                    return $file_storage->functions[$function_id]->location;
                }

                if (!$function_id) {
                    return null;
                }

                return $this->functions->getStorage(null, $function_id)->location;
            }

            return $this->classlike_storage_provider->get($reference->symbol)->location;
        } catch (UnexpectedValueException $e) {
            error_log($e->getMessage());

            return null;
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    public function addTemporaryFileChanges(string $file_path, string $new_content, ?int $version = null): void
    {
        $this->file_provider->addTemporaryFileChanges($file_path, $new_content, $version);
    }

    public function removeTemporaryFileChanges(string $file_path): void
    {
        $this->file_provider->removeTemporaryFileChanges($file_path);
    }
}
