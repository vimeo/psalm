<?php

namespace Doctrine\ORM;

class EntityManager implements EntityManagerInterface
{
    /**
     * @template T
     * @param class-string<T> $entityName
     * @param mixed           $id
     *
     * @return null|T
     */
    public function getReference($entityName, $id)
    {
    }
}

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
