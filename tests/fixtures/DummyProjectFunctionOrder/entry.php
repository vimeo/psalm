<?php

namespace Vimeo\Test\DummyProjectFunctionOrder;

function testing(int $a): void {
    echo $a;
}

testing(a());
testing(A_CONST);
