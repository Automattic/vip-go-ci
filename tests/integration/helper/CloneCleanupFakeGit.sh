#!/bin/sh

if [ "$1" = clone ]; then
	printf '%s' "$3" > "$VIPGOCI_FAKE_GIT_LOG"
	mkdir "$3/partial"
	if [ "$VIPGOCI_FAKE_GIT_MODE" = clone_fail ]; then
		exit 1
	fi
	mkdir "$3/.git"
	exit 0
fi

if [ "$1" = -C ]; then
	if [ "$VIPGOCI_FAKE_GIT_MODE" = checkout_fail ]; then
		printf '%s' partial > "$2/partial/file"
		ln -s "$VIPGOCI_FAKE_GIT_SENTINEL" "$2/partial/unrelated"
		exit 1
	fi
	exit 0
fi

exit 1
