#!/bin/bash
#
# Entrypoint of the vip-go-ci image. Optionally clones the repository to
# scan, then runs vip-go-ci with the arguments given.
#
# Environment:
#   VIP_GO_CI_PHP              PHP binary to run vip-go-ci with.
#   VIP_GO_CI_REPO_PATH        Path of the repository scanned.
#   VIP_GO_CI_CLONE_REPOSITORY Repository to clone to VIP_GO_CI_REPO_PATH, as
#                              owner/name. When empty, the repository must
#                              already be at VIP_GO_CI_REPO_PATH.
#   VIP_GO_CI_CLONE_COMMIT     Commit to check out.
#   VIPGOCI_TOKEN              Token used to clone; also passed to vip-go-ci
#                              via --env-options.

set -euo pipefail

error() {
	echo "vip-go-ci-entrypoint: $*" >&2
	exit 1
}

: "${VIP_GO_CI_PHP:?VIP_GO_CI_PHP must be set}"
: "${VIP_GO_CI_REPO_PATH:?VIP_GO_CI_REPO_PATH must be set}"

if [ ! -x "$VIP_GO_CI_PHP" ] ; then
	error "PHP binary not available in this image: $VIP_GO_CI_PHP"
fi

# Check the PHP versions requested for linting are installed, to fail with a
# clear message rather than deep within the scan.
IFS=',' read -r -a lint_paths <<< "${VIPGOCI_LINT_PHP_VERSION_PATHS:-}"

for lint_path in "${lint_paths[@]}" ; do
	if [ ! -x "${lint_path#*:}" ] ; then
		error "PHP version for linting not available in this image: ${lint_path%%:*}"
	fi
done

# The repository scanned is untrusted: never run its hooks, fetch its
# submodules, or follow local file transports. Set via the environment, so
# that git commands run by vip-go-ci use them too, without changing the
# repository's config.
export GIT_CONFIG_NOSYSTEM=1
export GIT_TERMINAL_PROMPT=0
export GIT_CONFIG_COUNT=5
export GIT_CONFIG_KEY_0="core.hooksPath" GIT_CONFIG_VALUE_0="/dev/null"
export GIT_CONFIG_KEY_1="core.fsmonitor" GIT_CONFIG_VALUE_1="false"
export GIT_CONFIG_KEY_2="protocol.file.allow" GIT_CONFIG_VALUE_2="never"
export GIT_CONFIG_KEY_3="submodule.recurse" GIT_CONFIG_VALUE_3="false"
export GIT_CONFIG_KEY_4="fetch.recurseSubmodules" GIT_CONFIG_VALUE_4="false"

if [ -n "${VIP_GO_CI_CLONE_REPOSITORY:-}" ] ; then
	: "${VIP_GO_CI_CLONE_COMMIT:?VIP_GO_CI_CLONE_COMMIT must be set}"
	: "${VIPGOCI_TOKEN:?VIPGOCI_TOKEN must be set}"

	git init -q "$VIP_GO_CI_REPO_PATH"
	git -C "$VIP_GO_CI_REPO_PATH" remote add origin "https://github.com/$VIP_GO_CI_CLONE_REPOSITORY.git"

	# vip-go-ci needs the commit scanned, and history to diff it against the
	# pull request base. The token is passed via the environment, so that it
	# is neither in the process list nor written to disk.
	GIT_CONFIG_COUNT=6 \
	GIT_CONFIG_KEY_5="http.https://github.com/.extraheader" \
	GIT_CONFIG_VALUE_5="AUTHORIZATION: basic $(printf 'x-access-token:%s' "$VIPGOCI_TOKEN" | base64 -w 0)" \
		git -C "$VIP_GO_CI_REPO_PATH" fetch -q --no-tags origin \
			'+refs/heads/*:refs/remotes/origin/*' \
			"$VIP_GO_CI_CLONE_COMMIT"

	git -C "$VIP_GO_CI_REPO_PATH" checkout -q --detach "$VIP_GO_CI_CLONE_COMMIT"
else
	if [ ! -d "$VIP_GO_CI_REPO_PATH/.git" ] ; then
		error "No repository at $VIP_GO_CI_REPO_PATH"
	fi
fi

exec "$VIP_GO_CI_PHP" /opt/vip-go-ci/vip-go-ci.php --local-git-repo="$VIP_GO_CI_REPO_PATH" "$@"
