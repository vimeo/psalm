#!/usr/bin/env bash
# Measures one corpus job with base and head Psalm checkouts. See bin/perf/README.md.
# Exit 0 per project; 2 on usage error or missing tool. PERF_CORPUS=<path> overrides the corpus. Bash 3.2 (macOS).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd -P)"
CORPUS="${PERF_CORPUS:-$SCRIPT_DIR/corpus.yml}"
RERUN_RATIO=0.10

die() { echo "run-shard: $*" >&2; exit 2; }

usage() {
    die "usage: run-shard.sh --job <id> --base <dir> --head <dir> --base-sha <sha> --head-sha <sha> --out <dir> [--work <dir>]"
}

JOB='' BASE_DIR='' HEAD_DIR='' BASE_SHA='' HEAD_SHA='' OUT='' WORK="${RUNNER_TEMP:-/tmp}/perf-work"
while [ $# -gt 0 ]; do
    [ $# -ge 2 ] || usage
    case "$1" in
        --job) JOB=$2 ;;
        --base) BASE_DIR=$2 ;;
        --head) HEAD_DIR=$2 ;;
        --base-sha) BASE_SHA=$2 ;;
        --head-sha) HEAD_SHA=$2 ;;
        --out) OUT=$2 ;;
        --work) WORK=$2 ;;
        *) usage ;;
    esac
    shift 2
done
[ -n "$JOB" ] && [ -n "$BASE_DIR" ] && [ -n "$HEAD_DIR" ] && [ -n "$BASE_SHA" ] && [ -n "$HEAD_SHA" ] \
    && [ -n "$OUT" ] || usage

for bin in jq git php composer yq; do
    command -v "$bin" >/dev/null 2>&1 || die "missing required tool: $bin"
done
[ -x /usr/bin/time ] || die "/usr/bin/time is required"
if command -v timeout >/dev/null 2>&1; then TIMEOUT_BIN=timeout
elif command -v gtimeout >/dev/null 2>&1; then TIMEOUT_BIN=gtimeout
else die "GNU timeout is required (gtimeout on macOS: brew install coreutils)"
fi
if [ "$(uname -s)" = Darwin ]; then TIME_FLAG=-l; else TIME_FLAG=-v; fi

[ -f "$CORPUS" ] || die "missing $CORPUS"
CORPUS="$(cd "$(dirname "$CORPUS")" && pwd -P)/$(basename "$CORPUS")"
for dir in "$BASE_DIR" "$HEAD_DIR"; do
    [ -f "$dir/psalm" ] && [ -f "$dir/vendor/autoload.php" ] || die "not a Psalm checkout with dependencies: $dir"
done
BASE_DIR="$(cd "$BASE_DIR" && pwd -P)"
HEAD_DIR="$(cd "$HEAD_DIR" && pwd -P)"
mkdir -p "$OUT/base" "$OUT/head" "$WORK"
OUT="$(cd "$OUT" && pwd -P)"
WORK="$(cd "$WORK" && pwd -P)"
case "$CORPUS" in
    *.yml | *.yaml) yq -o=json "$CORPUS" > "$WORK/corpus.json" || die "cannot convert $CORPUS"; CORPUS="$WORK/corpus.json" ;;
esac

PROJECTS="$(jq -r --arg j "$JOB" '.jobs[$j] // empty | .[]' "$CORPUS")"
[ -n "$PROJECTS" ] || die "unknown job '$JOB' in $CORPUS"
MEMORY_LIMIT="$(jq -r '.memory_limit' "$CORPUS")"
INSTALL_TIMEOUT="$(jq -r '.install_timeout' "$CORPUS")"
[ "$INSTALL_TIMEOUT" -gt 0 ] 2>/dev/null || die "install_timeout in $CORPUS must be a positive integer"

# Without this Psalm re-execs (xdebug-handler); only --memory-limit caps Psalm, not `php -d`.
export PSALM_ALLOW_XDEBUG=1
export COMPOSER_NO_INTERACTION=1
PHP_FLAGS=(-d memory_limit=-1)
while IFS= read -r setting; do
    PHP_FLAGS+=(-d "$setting")
done < <(jq -r '.php_ini | to_entries[] | "\(.key)=\(.value)"' "$CORPUS")

cpu_model() {
    if [ "$(uname -s)" = Darwin ]; then sysctl -n machdep.cpu.brand_string
    else sed -n 's/^model name[[:space:]]*: //p' /proc/cpuinfo | sed -n '1p'
    fi
}
CPU_MODEL="$(cpu_model 2>/dev/null || true)"
CPU_MODEL="${CPU_MODEL:-unknown}"

now() { php -r 'echo microtime(true);'; }
elapsed() { awk -v a="$1" -v b="$2" 'BEGIN { printf "%.2f", b - a }'; }

num() { if [ -n "${1:-}" ]; then printf '%s' "$1"; else printf 'null'; fi; }

NAME='' PROJ='' RUNDIR='' TIMEOUT='' REASON_FILE="$WORK/reason"

fail() { printf '%s' "$1" > "$REASON_FILE"; exit 1; }

field() { jq -r --arg p "$NAME" ".projects[\$p].$1 // empty" "$CORPUS"; }

# fetch_and_install <repo> <ref> <composer true|false> <before_install snippet> <tag>
fetch_and_install() {
    local repo=$1 ref=$2 composer=$3 before_install=$4 tag=$5 attempt=1
    git -C "$PROJ" init -q || return 1
    git -C "$PROJ" remote add origin "$repo" || return 1
    until git -C "$PROJ" fetch -q --depth 1 origin "$ref"; do
        [ "$attempt" -lt 3 ] || { echo "git fetch of $ref from $repo failed" >&2; return 1; }
        attempt=$((attempt + 1))
        sleep 5
    done
    git -C "$PROJ" -c advice.detachedHead=false checkout -q FETCH_HEAD || return 1
    if [ -n "$before_install" ]; then (cd "$PROJ" && bash -c "$before_install") || return 1; fi
    if [ "$composer" = true ] && [ -f "$PROJ/composer.json" ]; then
        # Some projects require self.version; a checkout has no tag.
        (cd "$PROJ" && COMPOSER_ROOT_VERSION="${tag#v}" composer update --no-dev --no-scripts --no-plugins \
            --ignore-platform-reqs --no-interaction --no-progress) || return 1
    fi
}
export -f fetch_and_install

install_project() {
    local repo ref tag composer before_install log="$RUNDIR/install.log" rc=0
    repo="$(field repo)" ref="$(field ref)" tag="$(field tag)" before_install="$(field before_install)"
    composer="$(jq -r --arg p "$NAME" 'if .projects[$p].composer == false then "false" else "true" end' "$CORPUS")"
    [ -n "$repo" ] && [ -n "$ref" ] && [ -n "$TIMEOUT" ] || fail "corpus entry of $NAME lacks repo, ref or timeout"

    rm -rf "$PROJ"
    mkdir -p "$PROJ"
    export PROJ
    "$TIMEOUT_BIN" -k 20 "$INSTALL_TIMEOUT" bash -c 'fetch_and_install "$@"' _ "$repo" "$ref" "$composer" "$before_install" "$tag" \
        > "$log" 2>&1 < /dev/null || rc=$?
    if [ "$rc" -eq 124 ] || [ "$rc" -eq 137 ]; then
        fail "install exceeded ${INSTALL_TIMEOUT}s"
    elif [ "$rc" -ne 0 ]; then
        fail "install failed: $(grep -v '^[[:space:]]*$' "$log" | tail -n 3 | tr '\n' ' ' | cut -c1-300)"
    fi
}

# Generates psalm-perf.xml; loadLibraryPlugins=false only if both checkouts know it. after_install may patch it.
write_config() {
    local flag='' after_install
    if grep -q loadLibraryPlugins "$BASE_DIR/config.xsd" 2>/dev/null \
        && grep -q loadLibraryPlugins "$HEAD_DIR/config.xsd" 2>/dev/null; then
        flag=--no-library-plugins
    fi
    php "$SCRIPT_DIR/perf.php" config --corpus "$CORPUS" --project "$NAME" $flag > "$PROJ/psalm-perf.xml" \
        || fail "cannot generate psalm-perf.xml"
    after_install="$(field after_install)"
    if [ -n "$after_install" ]; then
        (cd "$PROJ" && bash -c "$after_install") > "$RUNDIR/after_install.log" 2>&1 || fail "after_install failed: $(tail -n 3 "$RUNDIR/after_install.log" | tr '\n' ' ' | cut -c1-300)"
    fi
    cp "$PROJ/psalm-perf.xml" "$OUT/$NAME.psalm.xml"
}

# Y from Psalm's "used Y MB" line; grep -a because logs may contain NUL bytes.
parse_psalm_mb() {
    { grep -aE 'Checks took .* seconds and used .*MB of memory' "$1" || true; } | tail -n 1 \
        | sed -E 's/.*used ([0-9.,]+)MB.*/\1/' | tr -d ','
}

# /usr/bin/time output (GNU -v or BSD -l): TIME_CPU (user + sys seconds), TIME_RSS_KB, TIME_SIGNAL.
TIME_CPU='' TIME_RSS_KB='' TIME_SIGNAL=''
parse_time() {
    TIME_CPU='' TIME_RSS_KB='' TIME_SIGNAL=''
    [ -s "$1" ] || return 0
    if grep -q 'Maximum resident set size' "$1"; then
        TIME_CPU="$(awk -F': ' '/User time|System time/ {s += $2} END {if (NR) printf "%.2f", s}' "$1")"
        TIME_RSS_KB="$(awk -F': ' '/Maximum resident set size/ {print $2; exit}' "$1")"
        TIME_SIGNAL="$(sed -n 's/.*Command terminated by signal \([0-9][0-9]*\).*/\1/p' "$1" | sed -n '1p')"
    else
        TIME_CPU="$(awk '/ real .* user .* sys/ {printf "%.2f", $3 + $5; exit}' "$1")"
        TIME_RSS_KB="$(awk '/maximum resident set size/ {printf "%d", $1 / 1024; exit}' "$1")"
    fi
}

# Fatal-error lines first, then the end of the log; ANSI colours stripped; at most 4000 bytes.
log_tail() {
    {
        { grep -aE 'Fatal error|Allowed memory size|Hard memory limit' "$1" || true; } | sed -n '1,5p'
        tail -n 30 "$1"
    } | sed $'s/\033\\[[0-9;]*m//g' | cut -c1-300 | head -c 4000 || true
}

# classify <exit code> <wall> <log> <report>: RUN_STATUS (ok|crash|timeout|error), RUN_REASON, RUN_MEMORY_CRASH.
RUN_STATUS='' RUN_REASON='' RUN_MEMORY_CRASH=false
classify() {
    local rc=$1 wall=$2 log=$3 report=$4 code=$1
    RUN_REASON='' RUN_MEMORY_CRASH=false
    if [ -n "$TIME_SIGNAL" ]; then code=$((128 + TIME_SIGNAL)); fi
    if [ "$rc" -ge 125 ] && [ "$rc" -le 127 ]; then
        RUN_STATUS=error
        RUN_REASON="launcher failed with exit code $rc: $(grep -av '^[[:space:]]*$' "$log" | tail -n 1 | cut -c1-200)"
    elif [ "$rc" -eq 124 ] || { [ "$rc" -eq 137 ] && awk -v w="$wall" -v l="$TIMEOUT" 'BEGIN { exit !(w >= l) }'; }; then
        RUN_STATUS=timeout
        RUN_REASON="exceeded ${TIMEOUT}s"
    elif { [ "$code" -eq 0 ] || [ "$code" -eq 2 ]; } && grep -aq 'Checks took' "$log" && [ -s "$report" ]; then
        RUN_STATUS=ok
    else
        RUN_STATUS=crash
        if grep -aqE 'Allowed memory size|Hard memory limit' "$log"; then
            RUN_MEMORY_CRASH=true
            RUN_REASON="PHP memory limit exceeded"
        elif [ "$code" -eq 137 ]; then
            RUN_MEMORY_CRASH=true
            RUN_REASON="killed by SIGKILL before the ${TIMEOUT}s timeout (out of memory)"
        else
            RUN_REASON="Psalm exited with code $code"
        fi
    fi
}

# run_once <base|head> <n>: one analysis, recorded as $RUNDIR/<rev>-<n>.json (+ .log, .report.json).
run_once() {
    local rev=$1 n=$2 psalm_dir prefix rc=0 start end wall mb='' tail_text=''
    psalm_dir=$BASE_DIR
    if [ "$rev" = head ]; then psalm_dir=$HEAD_DIR; fi
    prefix="$RUNDIR/$rev-$n"

    start="$(now)"
    (cd "$PROJ" && /usr/bin/time "$TIME_FLAG" -o "$prefix.time" "$TIMEOUT_BIN" -k 20 "$TIMEOUT" \
        php "${PHP_FLAGS[@]}" "$psalm_dir/psalm" --root="$PROJ" -c "$PROJ/psalm-perf.xml" --no-cache --no-progress \
        --ignore-baseline --threads=1 --output-format=console --report="$prefix.report.json" \
        --memory-limit="$MEMORY_LIMIT") > "$prefix.log" 2>&1 < /dev/null || rc=$?
    end="$(now)"
    wall="$(elapsed "$start" "$end")"

    parse_time "$prefix.time"
    classify "$rc" "$wall" "$prefix.log" "$prefix.report.json"
    if [ "$RUN_STATUS" = ok ]; then mb="$(parse_psalm_mb "$prefix.log")"; else tail_text="$(log_tail "$prefix.log")"; fi

    jq -n --arg status "$RUN_STATUS" --arg reason "$RUN_REASON" --argjson memory_crash "$RUN_MEMORY_CRASH" \
        --argjson exit_code "$rc" --argjson wall "$wall" --argjson cpu "$(num "$TIME_CPU")" \
        --argjson rss "$(num "$TIME_RSS_KB")" --argjson mb "$(num "$mb")" --arg tail "$tail_text" '{
            status: $status, reason: (if $reason == "" then null else $reason end), memory_crash: $memory_crash,
            exit_code: $exit_code, wall: $wall, cpu: $cpu, max_rss_kb: $rss, psalm_mb: $mb,
            log_tail: (if $tail == "" then null else $tail end)
        }' > "$prefix.json"
}

run_field() { jq -r ".$3" "$RUNDIR/$1-$2.json"; }

# Both first runs ok and wall times differ by more than RERUN_RATIO of base.
needs_rerun() {
    [ "$(run_field base 1 status)" = ok ] && [ "$(run_field head 1 status)" = ok ] || return 1
    awk -v b="$(run_field base 1 wall)" -v h="$(run_field head 1 wall)" -v r="$RERUN_RATIO" \
        'BEGIN { d = h - b; if (d < 0) d = -d; exit !(d > r * b) }'
}

write_error() {
    local rev=$1 sha=$2 reason=$3
    jq -n --arg project "$NAME" --arg rev "$rev" --arg sha "$sha" --arg reason "$reason" --arg cpu "$CPU_MODEL" '{
        schema: 1, project: $project, rev: $rev, sha: $sha, status: "error", reason: $reason, memory_crash: false,
        psalm_mb: null, wall: null, cpu: null, max_rss_kb: null, exit_code: null, runs: [], cpu_model: $cpu,
        issue_count: null, log_tail: null
    }' > "$OUT/$rev/$NAME.json"
}

# Status, memory and logs come from the first run only; a re-run contributes just to the minimum wall and cpu.
aggregate() {
    jq -s --arg project "$NAME" --arg rev "$1" --arg sha "$2" --arg cpu "$CPU_MODEL" '
        .[0] as $d
        | ($d.status == "ok") as $ok
        | {
            schema: 1, project: $project, rev: $rev, sha: $sha, status: $d.status, reason: $d.reason,
            memory_crash: $d.memory_crash, psalm_mb: $d.psalm_mb,
            wall: (if $ok then map(select(.status == "ok") | .wall) | min else $d.wall end),
            cpu: (if $ok then map(select(.status == "ok" and .cpu != null) | .cpu) | min else $d.cpu end),
            max_rss_kb: $d.max_rss_kb, exit_code: $d.exit_code,
            runs: map({status, wall, cpu}), cpu_model: $cpu, issue_count: null, log_tail: $d.log_tail
        }' "$RUNDIR/$1"-[0-9].json
}

# write_result <base|head> <sha>: <out>/<rev>/<project>.json and, for an ok run, .issues.json.
write_result() {
    local rev=$1 sha=$2 result report count
    result="$(aggregate "$rev" "$sha")"
    report="$RUNDIR/$rev-1.report.json"
    if [ "$(jq -r '.status' <<< "$result")" = ok ]; then
        if count="$(php "$SCRIPT_DIR/perf.php" normalize "$report" "$PROJ" "$OUT/$rev/$NAME.issues.json")"; then
            result="$(jq --argjson c "$count" '.issue_count = $c' <<< "$result")"
        else
            rm -f "$OUT/$rev/$NAME.issues.json"
            result="$(jq '.status = "error" | .reason = "issue normalisation failed" | .psalm_mb = null' <<< "$result")"
        fi
    fi
    printf '%s\n' "$result" > "$OUT/$rev/$NAME.json"
}

measure_project() {
    install_project
    write_config
    run_once base 1
    run_once head 1
    if needs_rerun; then
        echo "run-shard: $NAME wall times differ by more than $(awk -v r="$RERUN_RATIO" 'BEGIN { printf "%g", r * 100 }') %, re-running in reverse order" >&2
        run_once head 2
        run_once base 2
    fi
    write_result base "$BASE_SHA"
    write_result head "$HEAD_SHA"
}

for NAME in $PROJECTS; do
    PROJ="$WORK/$NAME"
    RUNDIR="$WORK/runs/$NAME"
    TIMEOUT="$(field timeout)"
    rm -rf "$RUNDIR" "$REASON_FILE" "$OUT/base/$NAME".* "$OUT/head/$NAME".* "$OUT/$NAME.psalm.xml"
    mkdir -p "$RUNDIR"
    echo "run-shard: $NAME" >&2

    # A subshell run as a plain command keeps `set -e`: a failure aborts only this project.
    set +e
    (set -e; measure_project)
    status=$?
    set -e
    if [ "$status" -ne 0 ]; then
        reason="harness error (exit $status)"
        if [ -s "$REASON_FILE" ]; then reason="$(cat "$REASON_FILE")"; fi
        echo "run-shard: $NAME failed: $reason" >&2
        [ -f "$OUT/base/$NAME.json" ] || write_error base "$BASE_SHA" "$reason"
        [ -f "$OUT/head/$NAME.json" ] || write_error head "$HEAD_SHA" "$reason"
    fi
done
echo "run-shard: job $JOB done" >&2
