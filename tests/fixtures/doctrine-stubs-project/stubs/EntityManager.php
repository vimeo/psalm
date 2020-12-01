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
