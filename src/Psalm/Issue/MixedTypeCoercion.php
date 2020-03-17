<?php
namespace Psalm\Issue;

/**
 * @deprecated use more specific classes
 * @psalm-suppress UnusedClass
 */
class MixedTypeCoercion extends ArgumentIssue
{
    const ERROR_LEVEL = 1;
    const SHORTCODE = 119;
}
