<?php

namespace Doctrine\ORM;
/* final */class EntityManager implements EntityManagerInterface
{
    /**
     * {@inheritDoc}
     */
    public function getReference($entityName, $id)
    {
        return new stdClass;
    }
}
