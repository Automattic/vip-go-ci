#!/usr/bin/env bash
# Execute isolated tests with external HTTP replaced by checked-in fixtures.
set -euo pipefail

vipgoci_php_binary="${VIPGOCI_PHP_BINARY:-php}"
vipgoci_phpunit_binary="${VIPGOCI_PHPUNIT:-$(command -v phpunit || true)}"
if [[ -z "$vipgoci_phpunit_binary" ]]; then
    echo 'PHPUnit not found; set VIPGOCI_PHPUNIT to the PHPUnit executable or PHAR.' >&2
    exit 1
fi
if [[ "${VIPGOCI_TESTING_LIVE_API:-false}" == 'true' ]]; then
    exec "$vipgoci_php_binary" "$vipgoci_phpunit_binary" "$@"
fi
vipgoci_test_ini_dir="$(mktemp -d)"
trap 'rm -rf "$vipgoci_test_ini_dir"' EXIT
vipgoci_disabled_functions="$("$vipgoci_php_binary" -r '
    echo implode(",", array_unique(array_filter(array_merge(
        array_map("trim", explode(",", ini_get("disable_functions"))),
        ["curl_exec", "curl_setopt", "curl_setopt_array", "curl_getinfo", "sleep"]
    ))));
')"
printf 'disable_functions=%s\n' "$vipgoci_disabled_functions" > "$vipgoci_test_ini_dir/offline-api.ini"
# An empty component resolves to the invoked interpreter's own default directory.
vipgoci_ini_scan_dir="${PHP_INI_SCAN_DIR-}"
export GIT_ALLOW_PROTOCOL=file
export PHP_INI_SCAN_DIR="$vipgoci_ini_scan_dir:$vipgoci_test_ini_dir"
"$vipgoci_php_binary" "$vipgoci_phpunit_binary" "$@"
