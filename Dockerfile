# Image with vip-go-ci, the PHP versions it lints with and its tools (PHPCS,
# coding standards and the SVG scanner), at pinned versions.
#
# Scans process untrusted code. Run in this container, they cannot see the
# host's processes, memory or files, and so cannot reach anything other than
# what is passed to the container. As everything needed is installed when the
# image is built, nothing is downloaded or installed at scan time.
#
# Build:
#
#   docker build -t vip-go-ci .
#
# PHP versions can be changed with --build-arg, e.g.
# --build-arg LINT_PHP_VERSIONS="8.2 8.3". Run, with the repository scanned
# cloned inside the container (see docker/entrypoint.sh):
#
#   docker run --rm --read-only --cap-drop ALL --security-opt no-new-privileges \
#     --tmpfs /tmp --tmpfs /work \
#     -e VIP_GO_CI_PHP=/usr/bin/php8.3 -e VIP_GO_CI_REPO_PATH=/work/repo \
#     -e VIP_GO_CI_CLONE_REPOSITORY=owner/name -e VIP_GO_CI_CLONE_COMMIT=<sha> \
#     -e VIPGOCI_TOKEN \
#     vip-go-ci --env-options=token=VIPGOCI_TOKEN --repo-owner=owner ...

FROM ubuntu:24.04@sha256:68ee22004a9109b44973313b02ff7b1de250f1de0f5ecb628d73f579aaff33ac

# PHP versions files can be linted with, and versions vip-go-ci, PHPCS and the
# SVG scanner can run with. All are installed side by side as /usr/bin/phpX.Y.
ARG LINT_PHP_VERSIONS="7.4 8.0 8.1 8.2 8.3 8.4"
ARG RUNTIME_PHP_VERSIONS="8.2 8.3 8.4"

ENV DEBIAN_FRONTEND=noninteractive

RUN set -eux; \
	apt-get update; \
	apt-get install -y --no-install-recommends ca-certificates git gnupg software-properties-common tini wget; \
	add-apt-repository -y ppa:ondrej/php; \
	packages=""; \
	for version in $LINT_PHP_VERSIONS; do \
		packages="$packages php$version-cli"; \
	done; \
	for version in $RUNTIME_PHP_VERSIONS; do \
		packages="$packages php$version-cli php$version-curl php$version-mbstring php$version-xml"; \
	done; \
	apt-get install -y --no-install-recommends $packages; \
	apt-get purge -y --auto-remove gnupg software-properties-common; \
	rm -rf /var/lib/apt/lists/*

# Install PHPCS, coding standards and the SVG scanner, at the versions pinned
# (with checksums) by tools-init.sh. Copied on its own, so that this layer is
# only rebuilt when the tools change. tools-init.sh refuses to run as root and
# installs to ~/vip-go-ci-tools, so run it as a temporary user, then move the
# tools to /opt, owned by root and readable by anyone (tools-init.sh creates
# them with mode 700). The vip-go-ci release tools-init.sh also downloads is
# removed, as the vip-go-ci copied below is used.
COPY tools-init.sh /tmp/tools-init.sh

RUN set -eux; \
	useradd --create-home --home-dir /tmp/tools-init tools-init; \
	su tools-init -s /bin/bash -c 'cd /tmp/tools-init && USERNAME=tools-init bash /tmp/tools-init.sh'; \
	mv /tmp/tools-init/vip-go-ci-tools /opt/vip-go-ci-tools; \
	rm -rf /opt/vip-go-ci-tools/vip-go-ci /tmp/tools-init.sh; \
	chown -R root:root /opt/vip-go-ci-tools; \
	chmod -R a+rX,go-w /opt/vip-go-ci-tools; \
	userdel --remove tools-init; \
	test -x /opt/vip-go-ci-tools/phpcs/bin/phpcs; \
	test -f /opt/vip-go-ci-tools/vip-go-svg-sanitizer/svg-scanner.php

COPY . /opt/vip-go-ci
RUN chmod -R a+rX,go-w /opt/vip-go-ci
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/vip-go-ci-entrypoint

LABEL org.opencontainers.image.source="https://github.com/Automattic/vip-go-ci" \
	org.opencontainers.image.description="vip-go-ci, with the PHP versions and tools it uses." \
	com.wpvip.vip-go-ci.lint-php-versions="$LINT_PHP_VERSIONS" \
	com.wpvip.vip-go-ci.runtime-php-versions="$RUNTIME_PHP_VERSIONS"

# Callers should run the container as an unprivileged user of their own,
# with HOME in a tmpfs; this is only the default.
USER nobody
ENV HOME=/tmp

ENTRYPOINT [ "/usr/bin/tini", "--", "/usr/local/bin/vip-go-ci-entrypoint" ]
