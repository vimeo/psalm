<?php

$email  = (string)$_GET['bar'];
$domain = substr_replace($email,[4], 4);
echo $domain; // prints @example.com