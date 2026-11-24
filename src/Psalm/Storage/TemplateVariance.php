<?php

declare(strict_types=1);

namespace Psalm\Storage;

/**
 * How a class template's type arguments compare, from the tag that declares it
 *
 * @psalm-immutable
 * @api
 */
enum TemplateVariance
{
    /** `@template T`: `Box<A>` fits `Box<B>` when `A` and `B` are the same type */
    case Invariant;

    /** `@template-covariant T`: `Source<A>` fits `Source<B>` when `A` fits `B` */
    case Covariant;

    /** `@template-contravariant T`: `Sink<A>` fits `Sink<B>` when `B` fits `A` */
    case Contravariant;
}
