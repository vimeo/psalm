#!/bin/sh -l
set -e

TAINT_ANALYSIS=""
if [ "$INPUT_SECURITY_ANALYSIS" = "true" ]; then
    TAINT_ANALYSIS="--taint-analysis"
fi

REPORT=""
if [ ! -z "$INPUT_REPORT_FILE" ]; then
    REPORT="--report=$INPUT_REPORT_FILE"
fi

SHOW_INFO=""
if [ "$INPUT_SHOW_INFO" = "true" ]; then
  SHOW_INFO="--show-info=true"
fi

PHP_VERSION=""
if [ -n "$INPUT_PHP_VERSION" ]; then
  PHP_VERSION="--php-version=$INPUT_PHP_VERSION"
fi

if [ -n "$INPUT_RELATIVE_DIR" ]
then
    if [ -d "$INPUT_RELATIVE_DIR" ]; then
        echo "changing directory into $INPUT_RELATIVE_DIR"
        cd "$INPUT_RELATIVE_DIR"
    else
    	echo "given relative_dir not existing"
	exit 1
    fi
fi

/composer/vendor/bin/psalm --force-jit --no-cache $TAINT_ANALYSIS $REPORT $SHOW_INFO $PHP_VERSION $*
