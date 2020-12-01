<?php

namespace Doctrine\ORM;

interface EntityManagerInterface
{
    /**
     * @param string $entityName The name of the entity type.
     * @param mixed  $id         The entity identifier.
     *
     * @return object|null The entity reference.
     */
    public function getReference($entityName, $id);
}
