#!/bin/bash

export PHP_CODESNIFFER_VER="3.2.0"
export WP_CODING_STANDARDS_VER="0.14.0"
export VIP_CODING_STANDARDS_VER="0.2.4"

export TMP_LOCK_FILE="$HOME/.vip-go-ci-tools-init.lck"

function lock_place() {
	# Get lock, if that fails, just exit
	if [ -f "$TMP_LOCK_FILE" ] ; then
		echo "$0: Lock in place, not doing anything."
		exit 0
	fi

	# Acquire lock
	touch "$TMP_LOCK_FILE"
}

function lock_remove() {
	rm -f "$TMP_LOCK_FILE"
}

lock_place


#
# Exit if running as root
#
if [ "$USERNAME" == "root" ] ; then
	echo "$0: Will not run as root, exiting"
	lock_remove
	exit 1

fi


if [ -d ~/vip-go-ci-tools ] ; then
	#
	# We have got the tools installed already,
	# only check in 33% of cases if we should
	# upgrade.
	#
	export TMP_RAND=`seq 1 3 | sort -R | head -n 1`

	if [ "$TMP_RAND" -ne "1" ] ; then
		echo "$0: Not due to update anything, exiting"
		lock_remove
		exit 1
	fi
fi


# Fetch the latest release tag of vip-go-ci
export VIP_GO_CI_VER=""

if [ -f ~/vip-go-ci-tools/vip-go-ci/latest-release.php ] ||
	[ -x ~/vip-go-ci-tools/vip-go-ci/latest-release.php ] ; then
	export VIP_GO_CI_VER=`php ~/vip-go-ci-tools/vip-go-ci/latest-release.php`
fi

if [ "$VIP_GO_CI_VER" == "" ] ; then
	# latest-release.php is not available, fetch it
	# and then fetch the latest release number of vip-go-ci
	TMP_FILE=`mktemp /tmp/vip-go-ci-latest-release-XXXXX.php`

	echo "$0: Trying to determine latest release of vip-go-ci, need to fetch latest-release.php first..."
	wget -O "$TMP_FILE" https://raw.githubusercontent.com/Automattic/vip-go-ci/master/latest-release.php && \
	chmod u+x "$TMP_FILE" && \
	export VIP_GO_CI_VER=`php $TMP_FILE` && \
	rm "$TMP_FILE" && \
	echo "$0: Latest release of vip-go-ci is: $VIP_GO_CI_VER"
fi

# The release number is not available at all, abort
if [ "$VIP_GO_CI_VER" == "" ] ; then
	echo "$0: Could not determine latest release of vip-go-ci -- aborting";
	lock_remove
	exit 1
fi



if [ -d ~/vip-go-ci-tools ] ; then
	# Tools installed, check if versions installed match with
	# the versions specified in the current version of this file.
	# If not, remove what is already installed and re-install

	# Assume that no re-install is needed
	export TMP_DO_DELETE="0"


	for TMP_FILE in	"vip-coding-standards-$VIP_CODING_STANDARDS_VER.txt" "wp-coding-standards-$WP_CODING_STANDARDS_VER.txt" "php-codesniffer-$PHP_CODESNIFFER_VER.txt" "vip-go-ci-$VIP_GO_CI_VER.txt" ; do
		if [ ! -f ~/vip-go-ci-tools/$TMP_FILE ] ; then
			export TMP_DO_DELETE="1"
		fi
	done

	if [ "$TMP_DO_DELETE" -eq "1" ] ; then
		echo "$0: Detected obsolete vip-go-ci tools, removing them"
		# One or more of the versions do not match,
		# remove and reinstall
		rm -rf ~/vip-go-ci-tools
		echo "$0: Removed tools"
	fi
fi


if [ -d ~/vip-go-ci-tools ] ; then
	echo "$0: Nothing to update, exiting"
	lock_remove
	exit 0
else

	#
	# No tools installed, do install them,
	#
	echo "$0: No vip-go-ci tools present, will install"

	TMP_FOLDER=`mktemp -d /tmp/vip-go-ci-tools-XXXXXX`

	cd $TMP_FOLDER && \
	wget "https://github.com/squizlabs/PHP_CodeSniffer/archive/$PHP_CODESNIFFER_VER.tar.gz" && \
	tar -zxvf "$PHP_CODESNIFFER_VER.tar.gz"  && \
	rm -fv "$PHP_CODESNIFFER_VER.tar.gz" && \
	mv "PHP_CodeSniffer-$PHP_CODESNIFFER_VER/" phpcs && \
	touch $TMP_FOLDER/php-codesniffer-$PHP_CODESNIFFER_VER.txt && \
	wget "https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/$WP_CODING_STANDARDS_VER.tar.gz" && \
	tar -zxvf "$WP_CODING_STANDARDS_VER.tar.gz"  && \
	rm -fv "$WP_CODING_STANDARDS_VER.tar.gz" && \
	mv WordPress-Coding-Standards-$WP_CODING_STANDARDS_VER/WordPress* phpcs/src/Standards/ && \
	touch $TMP_FOLDER/wp-coding-standards-$WP_CODING_STANDARDS_VER.txt && \
	wget "https://github.com/Automattic/VIP-Coding-Standards/archive/$VIP_CODING_STANDARDS_VER.tar.gz" && \
	tar -zxvf "$VIP_CODING_STANDARDS_VER.tar.gz" && \
	mv "VIP-Coding-Standards-$VIP_CODING_STANDARDS_VER/WordPressVIPMinimum/" phpcs/src/Standards/  && \
	mv "VIP-Coding-Standards-$VIP_CODING_STANDARDS_VER/WordPress-VIP-Go/" phpcs/src/Standards/  && \
	rm -f "$VIP_CODING_STANDARDS_VER".tar.gz && \
	touch $TMP_FOLDER/vip-coding-standards-$VIP_CODING_STANDARDS_VER.txt && \
	wget "https://github.com/Automattic/vip-go-ci/archive/$VIP_GO_CI_VER.tar.gz" && \
	tar -zxvf "$VIP_GO_CI_VER.tar.gz" && \
	mv "vip-go-ci-$VIP_GO_CI_VER" vip-go-ci && \
	rm -f "$VIP_GO_CI_VER.tar.gz" && \
	touch "$TMP_FOLDER/vip-go-ci-$VIP_GO_CI_VER.txt" && \
	mv $TMP_FOLDER ~/vip-go-ci-tools && \

	# Note that the last action above is atomic:
	# Either moving the folder succeeds, and the tools
	# are all installed, or it fails and no tools are installed.

	echo "$0: Installation of tools finished"
fi

lock_remove
