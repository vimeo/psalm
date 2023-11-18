#!/bin/bash

set -eu

function get_seeded_random() {
  openssl enc -aes-256-ctr -pass pass:"vimeo/psalm" -nosalt </dev/zero 2>/dev/null
}

function run {
  local -r chunk_count="$1"
  local -r chunk_number="$2"
  local -r parallel_processes="$3"

  local -r phpunit_cmd='
echo "::group::{}";
vendor/phpunit/phpunit/phpunit --log-junit build/phpunit/logs/{_}.xml --colors=always {};
exit_code=$?;
echo ::endgroup::;
if [[ "$exit_code" -ne 0 ]]; then
    echo "::error::{}";
fi;
exit "$exit_code"'

  mkdir -p build/parallel/ build/phpunit/logs/

  find tests -name '*Test.php' | shuf --random-source=<(get_seeded_random) > build/tests_all
  # split incorrectly splits the lines by byte size, which means that the number of tests per file are as evenly distributed as possible
  #split --number="l/$chunk_number/$chunk_count" build/tests_all > build/tests_split
  local -r lines=$(wc -l <build/tests_all)
  local -r chunk_lines=$(( $lines / $chunk_count ))
  local -r rest=$(( $lines % $chunk_count ))
  local start_line
  local end_line
  if [[ $chunk_number -le $rest ]]
  then
      start_line=$(( ($chunk_number - 1) * $chunk_lines + $chunk_number ))
      end_line=$(( $chunk_number * $chunk_lines + $chunk_number ))
  else
      start_line=$(( ($chunk_number - 1) * $chunk_lines + $rest + 1 ))
      end_line=$(( $chunk_number * $chunk_lines + $rest ))
  fi
  awk "NR==$start_line,NR==$end_line" <build/tests_all > build/tests_split

  parallel --group -j"$parallel_processes" --rpl {_}\ s/\\//_/g --joblog build/parallel/jobs.log "$phpunit_cmd" < build/tests_split
}

if [ -z "${CHUNK_COUNT:-}" ]; then echo "Did not find env var CHUNK_COUNT."; exit 1; fi
if [ -z "${CHUNK_NUMBER:-}" ]; then echo "Did not find env var CHUNK_NUMBER."; exit 1; fi
if [ -z "${PARALLEL_PROCESSES:-}" ]; then echo "Did not find env var PARALLEL_PROCESSES."; exit 1; fi

run "$CHUNK_COUNT" "$CHUNK_NUMBER" "$PARALLEL_PROCESSES"
