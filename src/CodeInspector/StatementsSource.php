<?php

namespace CodeInspector;

interface StatementsSource
{
    public function getNamespace();

    public function getAliasedClasses();

    public function getAbsoluteClass();

    public function getClassName();

    public function getClassChecker();

    /**
     * @return \PhpParser\Node\Name
     */
    public function getParentClass();

    public function getFileName();

    public function isStatic();

    public function getSource();
}
