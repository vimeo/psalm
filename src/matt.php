<?php
namespace Matt;

interface I {}

class C
{
    public function getI(): I
    {
        return new class implements I
        {

        };
    }
}
