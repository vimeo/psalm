<?php

// PHP 5.6 port of https://github.com/nikic/PHP-Parser/blob/master/lib/PhpParser/NameContext.php

namespace Psalm\Visitor;

use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt;

class NameContext
{
    /** @var null|Name Current namespace */
    protected $namespace;

    /** @var Name[][] Map of format [aliasType => [aliasName => originalName]] */
    protected $aliases = [];

    /** @var Name[][] Same as $aliases but preserving original case */
    protected $origAliases = [];

    /** @var ErrorHandler Error handler */
    protected $errorHandler;

    const SPECIAL_CLASS_NAMES = [
        'self'   => true,
        'parent' => true,
        'static' => true,
    ];

    /**
     * Create a name context.
     *
     * @param ErrorHandler $errorHandler Error handling used to report errors
     */
    public function __construct(ErrorHandler $errorHandler) {
        $this->errorHandler = $errorHandler;
    }

    /**
     * Start a new namespace.
     *
     * This also resets the alias table.
     *
     * @param Name|null $namespace Null is the global namespace
     * @return void
     */
    public function startNamespace(Name $namespace = null) {
        $this->namespace = $namespace;
        $this->origAliases = $this->aliases = [
            Stmt\Use_::TYPE_NORMAL   => [],
            Stmt\Use_::TYPE_FUNCTION => [],
            Stmt\Use_::TYPE_CONSTANT => [],
        ];
    }

    /**
     * Add an alias / import.
     *
     * @param Name   $name        Original name
     * @param string $aliasName   Aliased name
     * @param int    $type        One of Stmt\Use_::TYPE_*
     * @param array  $errorAttrs Attributes to use to report an error
     * @return void
     */
    public function addAlias(Name $name, $aliasName, $type, array $errorAttrs = []) {
        // Constant names are case sensitive, everything else case insensitive
        if ($type === Stmt\Use_::TYPE_CONSTANT) {
            $aliasLookupName = $aliasName;
        } else {
            $aliasLookupName = strtolower($aliasName);
        }

        if (isset($this->aliases[$type][$aliasLookupName])) {
            $typeStringMap = [
                Stmt\Use_::TYPE_NORMAL   => '',
                Stmt\Use_::TYPE_FUNCTION => 'function ',
                Stmt\Use_::TYPE_CONSTANT => 'const ',
            ];

            $this->errorHandler->handleError(new Error(
                sprintf(
                    'Cannot use %s%s as %s because the name is already in use',
                    $typeStringMap[$type], $name, $aliasName
                ),
                $errorAttrs
            ));
            return;
        }

        $this->aliases[$type][$aliasLookupName] = $name;
        $this->origAliases[$type][$aliasName] = $name;
    }

    /**
     * Get resolved name.
     *
     * @param Name $name Name to resolve
     * @param int  $type One of Stmt\Use_::TYPE_{FUNCTION|CONSTANT}
     *
     * @return null|Name Resolved name, or null if static resolution is not possible
     */
    public function getResolvedName(Name $name, $type) {
        // don't resolve special class names
        if ($type === Stmt\Use_::TYPE_NORMAL
            && array_key_exists(strtolower($name->parts[0]), self::SPECIAL_CLASS_NAMES)
        ) {
            if (!$name->isUnqualified()) {
                $this->errorHandler->handleError(new Error(
                    sprintf("'\\%s' is an invalid class name", $name->toString()),
                    $name->getAttributes()
                ));
            }
            return $name;
        }

        // fully qualified names are already resolved
        if ($name->isFullyQualified()) {
            return $name;
        }

        // Try to resolve aliases
        if (null !== $resolvedName = $this->resolveAlias($name, $type)) {
            return $resolvedName;
        }

        if ($type !== Stmt\Use_::TYPE_NORMAL && $name->isUnqualified()) {
            if (null === $this->namespace) {
                // outside of a namespace unaliased unqualified is same as fully qualified
                return new FullyQualified($name, $name->getAttributes());
            }

            // Cannot resolve statically
            return null;
        }

        // if no alias exists prepend current namespace
        return FullyQualified::concat($this->namespace, $name, $name->getAttributes());
    }

    /**
     * Get resolved alias
     *
     * @param Name $name Name to resolve
     * @param int  $type One of Stmt\Use_::TYPE_{FUNCTION|CONSTANT}
     *
     * @return null|Name Resolved name, or null if static resolution is not possible
     */
    private function resolveAlias(Name $name, $type) {
        $firstPart = $name->getFirst();

        if ($name->isQualified()) {
            // resolve aliases for qualified names, always against class alias table
            $checkName = strtolower($firstPart);
            if (isset($this->aliases[Stmt\Use_::TYPE_NORMAL][$checkName])) {
                $alias = $this->aliases[Stmt\Use_::TYPE_NORMAL][$checkName];
                return FullyQualified::concat($alias, $name->slice(1), $name->getAttributes());
            }
        } elseif ($name->isUnqualified()) {
            // constant aliases are case-sensitive, function aliases case-insensitive
            $checkName = $type === Stmt\Use_::TYPE_CONSTANT ? $firstPart : strtolower($firstPart);
            if (isset($this->aliases[$type][$checkName])) {
                // resolve unqualified aliases
                return new FullyQualified($this->aliases[$type][$checkName], $name->getAttributes());
            }
        }

        // No applicable aliases
        return null;
    }
}
