<?php

namespace Psalm\Issue;

final class TaintedLdap extends TaintedInput
{
    public const SHORTCODE = 254;
    public const MESSAGE = 'Detected tainted LDAP request';
}
