<?php

namespace StubPseudoMethod;

/**
 * @method static mixed transaction(\Closure $closure)
 * @method static string untouched()
 * @method mixed run(\Closure $closure)
 * @method string untouchedOnInstance()
 * @method string instanceByStatic()
 * @method static string notPublic()
 * @method string secret()
 */
class DB extends Facade
{
}

class ChildDB extends DB
{
}
