<?php

declare(strict_types=1);

namespace Psalm\Type;

/**
 * Call-site variance of a generic type argument: `Box<covariant T>`, `Box<contravariant T>`, `Box<*>`.
 * An argument without any of them is invariant.
 *
 * @api
 */
enum Variance: string
{
    case Covariant = 'covariant';
    case Contravariant = 'contravariant';
    case Bivariant = '*';
}
