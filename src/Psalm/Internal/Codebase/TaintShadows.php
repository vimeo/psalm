<?php

declare(strict_types=1);

namespace Psalm\Internal\Codebase;

use Psalm\Type\TaintKind;

/**
 * The place of a value in a string can remove some of its taints: after a literal start fixing the server of a URL
 * (`'https://example.com/' . $value`), the value can't choose that server. That only holds while the start stays
 * there. Text put before it (`$base . $url`) can make the start choose nothing (`$base` may be `/`), and a function
 * cutting the string (`substr($url, 20)`) can return the value alone.
 *
 * So a place in a string removes these taints from a flow, but keeps their shadows: what the flow had of them.
 * Every other removal of a taint, an escape, removes its shadow too. Where text is put before the value, or a builtin
 * cuts, transforms or reorders it, the flow gets back the taints it has the shadows of (`restored_taints` of a path).
 *
 * A shadow is never a sink: what follows a URL start can't choose its server, as long as the start stays.
 *
 * @internal
 * @psalm-immutable
 */
final class TaintShadows
{
    /** the taints a place in a string can remove: `ssrf`, `file`, `url_path` and `redirect` */
    public const SHADOWED = TaintKind::INPUT_SSRF
        | TaintKind::INPUT_FILE
        | TaintKind::INPUT_URL_PATH
        | TaintKind::INPUT_REDIRECT;

    public const ALL = TaintKind::SHADOW_SSRF
        | TaintKind::SHADOW_FILE
        | TaintKind::SHADOW_URL_PATH
        | TaintKind::SHADOW_REDIRECT;

    /**
     * What text put before a value gives back: whether it chooses a server or a file depends on all the text before it.
     * In the query or the path after the host (`url_path`, `redirect`), it stays there whatever comes before it.
     */
    public const BEFORE_VALUE = TaintKind::SHADOW_SSRF | TaintKind::SHADOW_FILE;

    /** INPUT_SSRF and INPUT_FILE are bits 9 and 10, their shadows 27 and 28 */
    private const SERVER_SHIFT = 18;

    /** INPUT_URL_PATH and INPUT_REDIRECT are bits 19 and 20, their shadows 29 and 30 */
    private const PATH_SHIFT = 10;

    /**
     * The shadows of the taints of $taints
     *
     * @psalm-pure
     */
    public static function of(int $taints): int
    {
        return (($taints & (TaintKind::INPUT_SSRF | TaintKind::INPUT_FILE)) << self::SERVER_SHIFT)
            | (($taints & (TaintKind::INPUT_URL_PATH | TaintKind::INPUT_REDIRECT)) << self::PATH_SHIFT);
    }

    /**
     * The taints of the shadows of $taints
     *
     * @psalm-pure
     */
    public static function restore(int $taints): int
    {
        return (($taints & (TaintKind::SHADOW_SSRF | TaintKind::SHADOW_FILE)) >> self::SERVER_SHIFT)
            | (($taints & (TaintKind::SHADOW_URL_PATH | TaintKind::SHADOW_REDIRECT)) >> self::PATH_SHIFT);
    }
}
