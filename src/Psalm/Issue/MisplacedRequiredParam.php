<?php
namespace Psalm\Issue;

/**
 * @psalm-suppress UnusedClass because it's deprecated
 */
class MisplacedRequiredParam extends CodeIssue
{
    const ERROR_LEVEL = 2;
    const SHORTCODE = 67;
}
