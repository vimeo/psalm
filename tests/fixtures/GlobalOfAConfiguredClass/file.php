<?php

$connection->query(1);

foreach ($connections as $pool) {
    $pool?->open('many');
}
