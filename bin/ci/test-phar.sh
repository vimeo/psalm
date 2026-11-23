#!/usr/bin/env bash

# Analyzes a small project with a built phar, with several threads: the worker pool and its event loop run, with the
# event loop driver of the running PHP (on PHP 8.6, revolt/event-loop's IoPollDriver, which uses PHP's Io\Poll
# classes: a phar whose scoper prefixed them crashed before analyzing anything). Fails unless the analysis completes
# without errors.
#
# Usage: bin/ci/test-phar.sh <psalm.phar>

set -e

phar=$(realpath "$1")
php -v

dir=$(mktemp -d)
trap 'rm -rf "$dir"' EXIT
mkdir "$dir/src"
# More files than threads: Psalm analyzes fewer files without forking
for i in $(seq 1 10); do
    cat > "$dir/src/Value$i.php" <<EOF
<?php

declare(strict_types=1);

namespace PharTest;

final class Value$i
{
    public function __construct(private readonly int \$value)
    {
    }

    public function get(): int
    {
        return \$this->value + $i;
    }
}
EOF
done
cat > "$dir/src/main.php" <<'EOF'
<?php

declare(strict_types=1);

namespace PharTest;

echo (new Value1(1))->get() + (new Value10(10))->get(), "\n";
EOF
cat > "$dir/composer.json" <<'EOF'
{"autoload": {"psr-4": {"PharTest\\": "src/"}}}
EOF
cat > "$dir/psalm.xml" <<'EOF'
<?xml version="1.0"?>
<psalm errorLevel="1" findUnusedBaselineEntry="true" findUnusedCode="false">
    <projectFiles>
        <directory name="src"/>
    </projectFiles>
</psalm>
EOF

cd "$dir"
composer dump-autoload --quiet
php "$phar" --threads=4 --no-cache --no-progress
