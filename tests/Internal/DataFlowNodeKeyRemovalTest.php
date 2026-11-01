<?php

declare(strict_types=1);

namespace Psalm\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Psalm\CodeLocation\Raw;
use Psalm\Internal\DataFlow\DataFlowNode;

final class DataFlowNodeKeyRemovalTest extends TestCase
{
    /**
     * @psalm-pure
     */
    private static function assignment(string $var_id, int $start): DataFlowNode
    {
        return DataFlowNode::getForAssignment($var_id, new Raw('<?php // code', 'file.php', 'file.php', $start, $start + 2));
    }

    public function testRemovingAKeyAgainWhereItIsRemovedAlreadyIsNotAnotherNode(): void
    {
        $array = self::assignment('$a', 1);
        $removed = DataFlowNode::getForKeyRemoval($array, self::assignment('$a without \'x\'', 3));

        self::assertNotSame($array, $removed);
        self::assertSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment('$a without \'x\'', 6)));
    }

    public function testAKeyRemovalHasTheLocationAndTheIdOfTheRemovalThatMadeIt(): void
    {
        $array = self::assignment('$a', 1);
        $removal = self::assignment('$a without \'x\'', 3);
        $other_removal = self::assignment('$a without \'x\'', 6);

        $removed = DataFlowNode::getForKeyRemoval($array, $removal);

        self::assertSame($removal->code_location, $removed->code_location);
        self::assertSame($removal->label, $removed->label);
        self::assertNotSame($removed->id, DataFlowNode::getForKeyRemoval($array, $other_removal)->id);
    }

    public function testRemovingAKeyAgainWhereItIsRemovedUnderOtherRemovalsIsNotAnotherNode(): void
    {
        $removed = DataFlowNode::getForKeyRemoval(self::assignment('$a', 1), self::assignment('$a without \'x\'', 3));
        $removed_other = DataFlowNode::getForKeyRemoval($removed, self::assignment('$a without \'y\'', 6));
        $removed_another = DataFlowNode::getForKeyRemoval($removed_other, self::assignment('$a without \'z\'', 9));

        self::assertNotSame($removed, $removed_other);
        self::assertNotSame($removed_other, $removed_another);
        self::assertSame($removed_another, DataFlowNode::getForKeyRemoval($removed_another, self::assignment('$a without \'x\'', 12)));
        self::assertSame($removed_another, DataFlowNode::getForKeyRemoval($removed_another, self::assignment('$a without \'y\'', 12)));
        self::assertSame($removed_another, DataFlowNode::getForKeyRemoval($removed_another, self::assignment('$a without \'z\'', 12)));
    }

    public function testRemovingTheKeyOfAnotherArrayOrAnotherKeyIsAnotherNode(): void
    {
        $removed = DataFlowNode::getForKeyRemoval(self::assignment('$a', 1), self::assignment('$a without \'x\'', 3));

        self::assertNotSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment('$a without \'xy\'', 6)));
        self::assertNotSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment('$a without \'\'', 6)));
        self::assertNotSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment('$b without \'x\'', 6)));

        $other = DataFlowNode::getForKeyRemoval(self::assignment('$a', 1), self::assignment('k1', 3));

        self::assertNotSame($other, DataFlowNode::getForKeyRemoval($other, self::assignment('k2', 6)));
    }

    public function testAKeyIsRemovedFromANodeThatIsOnlyNamedAfterTheRemoval(): void
    {
        $node = self::assignment('$a without \'x\'', 1);

        self::assertNotSame($node, DataFlowNode::getForKeyRemoval($node, self::assignment('$a without \'x\'', 3)));

        $property = DataFlowNode::getForPropertyFetch('Foo::$bar');

        self::assertNotSame($property, DataFlowNode::getForKeyRemoval($property, self::assignment('Foo::$bar', 3)));
    }

    public function testRemovalsWhoseLabelsHoldWhatTheIdIsMadeOfAreTold(): void
    {
        $array = self::assignment('$a', 1);
        $labels = [
            '$a without \'x: 3:\'',
            'k',
            '$a without \'3:x from file.php:1-3\'',
            '$a without \'key removed: 1:x\'',
            '$a without \'\'',
        ];

        foreach ($labels as $label) {
            $removed = DataFlowNode::getForKeyRemoval($array, self::assignment($label, 3));

            self::assertNotSame($array, $removed);
            self::assertSame($label, $removed->label);
            self::assertSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment($label, 6)));

            foreach ($labels as $other_label) {
                if ($other_label !== $label) {
                    self::assertNotSame($removed, DataFlowNode::getForKeyRemoval($removed, self::assignment($other_label, 6)));
                }
            }

            self::assertSame([$array->id => $array], DataFlowNode::combineParentNodes([$removed->id => $removed], [$array->id => $array]));
        }
    }

    public function testNodesRemovingAKeyAreMergedAwayWhereTheNodeTheyRemoveItFromIsMergedIn(): void
    {
        $array = self::assignment('$a', 1);
        $removed = DataFlowNode::getForKeyRemoval($array, self::assignment('$a without \'x\'', 3));
        $removed_other = DataFlowNode::getForKeyRemoval($removed, self::assignment('$a without \'y: 1:\'', 6));
        $unrelated = self::assignment('$b', 9);

        self::assertSame(
            [$array->id => $array],
            DataFlowNode::combineParentNodes([$removed->id => $removed], [$array->id => $array]),
        );
        self::assertSame(
            [$array->id => $array],
            DataFlowNode::combineParentNodes([$array->id => $array], [$removed_other->id => $removed_other]),
        );
        self::assertSame(
            [$removed->id => $removed],
            DataFlowNode::combineParentNodes([$removed_other->id => $removed_other], [$removed->id => $removed]),
        );
        self::assertSame(
            [$removed->id => $removed, $unrelated->id => $unrelated],
            DataFlowNode::combineParentNodes(
                [$removed->id => $removed, $unrelated->id => $unrelated],
                [$removed->id => $removed],
            ),
        );
        self::assertSame(
            [$removed_other->id => $removed_other, $unrelated->id => $unrelated],
            DataFlowNode::combineParentNodes([$removed_other->id => $removed_other], [$unrelated->id => $unrelated]),
        );
    }

    public function testNodesRemovingAKeyAreNotMergedAwayWhereTheNodeTheyRemoveItFromIsNotMergedIn(): void
    {
        $removed = DataFlowNode::getForKeyRemoval(self::assignment('$a', 1), self::assignment('$a without \'x\'', 3));
        $other_removed = DataFlowNode::getForKeyRemoval(self::assignment('$a', 6), self::assignment('$a without \'x\'', 9));

        self::assertSame(
            [$removed->id => $removed, $other_removed->id => $other_removed],
            DataFlowNode::combineParentNodes([$removed->id => $removed], [$other_removed->id => $other_removed]),
        );
    }

    public function testNodesRemovingAKeyAreMergedAwayThroughNodesNarrowingToAScalar(): void
    {
        $array = self::assignment('$a', 1);
        $removed = DataFlowNode::getForKeyRemoval($array, self::assignment('$a without \'x\'', 3));
        $narrowed_removed = DataFlowNode::getForNarrowingToScalar($removed);
        $removed_narrowed = DataFlowNode::getForKeyRemoval(
            DataFlowNode::getForNarrowingToScalar($array),
            self::assignment('$a without \'y\'', 6),
        );

        self::assertSame(
            [$array->id => $array],
            DataFlowNode::combineParentNodes([$narrowed_removed->id => $narrowed_removed], [$array->id => $array]),
        );
        self::assertSame(
            [$array->id => $array],
            DataFlowNode::combineParentNodes([$removed_narrowed->id => $removed_narrowed], [$array->id => $array]),
        );
        self::assertSame(
            [$removed->id => $removed],
            DataFlowNode::combineParentNodes([$narrowed_removed->id => $narrowed_removed], [$removed->id => $removed]),
        );
    }
}
