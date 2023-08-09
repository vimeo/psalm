<?php

echo 'Example output from failing autoloader';

// We exit the autoloader with a zero status, to make sure Psalm itself does NOT exit with that status, incorrectly implying success.
die(0);
