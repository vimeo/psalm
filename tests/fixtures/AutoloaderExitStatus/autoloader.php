<?php

// The following causes a zero exit status, which we explicitly want to ensure does NOT result in a zero exit status from Psalm itself.
die('Example output from failing autoloader');
