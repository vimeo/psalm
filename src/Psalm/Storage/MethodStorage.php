<?php

declare(strict_types=1);

namespace Psalm\Storage;

use Psalm\Type\Union;

final class MethodStorage extends FunctionLikeStorage
{
    use UnserializeMemoryUsageSuppressionTrait;
    public bool $is_static = false;

    public int $visibility = 0;

    public bool $final = false;

    public bool $final_from_docblock = false;

    public bool $abstract = false;

    public bool $overridden_downstream = false;

    public bool $overridden_somewhere = false;

    public bool $inheritdoc = false;

    public ?bool $inherited_return_type = false;

    public ?string $defining_fqcln = null;

    public bool $has_docblock_param_types = false;

    public bool $has_docblock_return_type = false;

    public bool $external_mutation_free = false;

    public bool $immutable = false;

    public bool $mutation_free_inferred = false;

    /**
     * @var ?array<string, bool>
     */
    public ?array $this_property_mutations = null;

    public ?Union $self_out_type = null;

    public ?Union $if_this_is_type = null;
    public bool $stubbed = false;

    public bool $probably_fluent = false;
}
