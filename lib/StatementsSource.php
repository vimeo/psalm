<?php

namespace CodeInspector;

interface StatementsSource
{
    public function getNamespace();

    public function getAliasedClasses();

    public function getAbsoluteClass();

    public function getClassName();

    /**
     * @return \PhpParser\Node\Name
     */
    public function getClassExtends();

    public function getFileName();

    public function isStatic();
}
