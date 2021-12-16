<?php

declare(strict_types=1);

namespace Psalm\Type;

use Psalm\Codebase;
use Psalm\Internal\Type\Comparator\TypeComparisonResult2;

use function array_filter;
use function count;

final class Intersection extends TypeNode
{
    /**
     * @psalm-suppress InvalidPropertyAssignmentValue
     * $types should always have at least 2 elements
     *
     * @var non-empty-list<TypeNode>
     * TODO non-empty-list<TypeNode&(!Intersection)>, intersections are never stored nested.
     */
    private $types = [];

    private function __construct(bool $negated)
    {
        $this->negated = $negated;
    }

    /**
     * Returns the Intersection of the given types, or null if the intersection is empty.
     *
     * @param non-empty-list<TypeNode> $types
     */
    public static function create(array $types, ?Codebase $codebase = null, bool $negated = false): ?TypeNode
    {
        $intersection = new self($negated);
        $unsimplified_intersection = new self($negated);
        $unsimplified_intersection->types = $types;
        $intersection = $intersection->addType($unsimplified_intersection, $codebase);

        return $intersection;
    }

    public function addType(TypeNode $type, ?Codebase $codebase = null): ?TypeNode
    {
        if ($type instanceof Intersection) {
            $new_type = $this;
            foreach ($type->getChildNodes() as $child_type) {
                $new_type = $new_type->addType($child_type, $codebase);
                if ($new_type === null) {
                    return $new_type;
                }
                if (!$new_type instanceof Intersection) {
                    $new_type = new Intersection($this->negated);
                }
            }
            if (count($new_type->types) === 1) {
                return $new_type->types[0];
            }
            return $new_type;
        }

        if (!$this->intersects($type, $codebase)->result) {
            return null;
        }

        if ($this->containedBy($type, $codebase)->result) {
            return $this;
        }

        if ($type->containedBy($this, $codebase)->result) {
            return $type;
        }

        // TODO try to consolidate unions? (eg `(int|string)&(string|float)` should become `string`)
        $this->types[] = $type;

        return $this;
    }

    /**
     * @return non-empty-list<TypeNode>
     */
    public function getChildNodes(): array
    {
        return $this->types;
    }

    public function containedBy(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2
    {
        $result = TypeComparisonResult2::false();
        foreach ($this->types as $child_type) {
            $result = $result->or($child_type->containedBy($other, $codebase));
            if ($result->result) {
                return $result;
            }
        }
        return $result;
    }

    public function intersects(TypeNode $other, ?Codebase $codebase = null): TypeComparisonResult2
    {
        $result = TypeComparisonResult2::true();
        foreach ($this->types as $child_type) {
            $result = $result->and($child_type->intersects($other, $codebase));
        }
        return $result;
    }

    // /**
    //  * @param  array<string, mixed> $phantom_classes
    //  *
    //  */
    // public function queueClassLikesForScanning(
    //     Codebase $codebase,
    //     ?FileStorage $file_storage = null,
    //     array $phantom_classes = []
    // ): void {
    //     $scanner_visitor = new \Psalm\Internal\TypeVisitor\TypeScanner(
    //         $codebase,
    //         $file_storage,
    //         $phantom_classes
    //     );

    //     $scanner_visitor->traverseArray($this->types);
    // }

    public function hasConditional(): bool
    {
        return (bool) array_filter(
            $this->types,
            function (Atomic $type): bool {
                return $type instanceof Atomic\TConditional;
            }
        );
    }
}
