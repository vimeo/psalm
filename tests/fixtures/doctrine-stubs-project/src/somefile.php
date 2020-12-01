<?php

use Doctrine\ORM\EntityManager;
interface I {}
/**
* @psalm-suppress InvalidReturnType
* @return EntityManager
*/
function em() {}
atan(em()->getReference(I::class, 1));
