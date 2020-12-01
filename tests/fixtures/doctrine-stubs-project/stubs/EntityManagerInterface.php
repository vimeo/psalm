<?php

namespace Doctrine\ORM;

interface EntityManagerInterface
{
    /**
     * @param class-string<T> $entityName
     * @param mixed           $id
     *
     * @return T|null
     *
     * @template T
     */
    public function getReference(string $entityName, $id);
}
