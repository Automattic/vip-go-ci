#!/bin/bash

export PHP_CODESNIFFER_VER="3.1.0"
export WP_CODING_STANDARDS_VER="0.14.0"
export VIP_CODING_STANDARDS_VER="0.2.2"

#
# FIXME: Only check in 33% of cases...
#

# Start by fetching the version-number from the latest
# release of vip-go-ci

export VIP_GO_CI_VER=`~/vip-go-ci-tools/vip-go-ci/latest-release.php`

if [ "$VIP_GO_CI_VER" == "" ] ; then
	TMP_FILE=`mktemp /tmp/vip-go-ci-latest-release-XXXXX.php`
	
	wget -O "$TMP_FILE" https://raw.githubusercontent.com/Automattic/vip-go-ci/master/latest-release.php && \
	chmod u+x "$TMP_FILE" && \
	export VIP_GO_CI_VER=`./$TMP_FILE`
fi

if [ "$VIP_GO_CI_VER" == "" ] ; then
	echo "Could not fetch version of latest release of vip-go-ci -- aborting";
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
		echo "Detected obsolete vip-go-ci tools"
		# One or more of the versions do not match,
		# remove and reinstall
		rm -rf ~/vip-go-ci-tools
		echo "Removed tools"
	fi
fi


if [ ! -d ~/vip-go-ci-tools ] ; then
	echo "No vip-go-ci tools present, will install"

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
	rm -f "$VIP_CODING_STANDARDS_VER".tar.gz && \
	touch $TMP_FOLDER/vip-coding-standards-$VIP_CODING_STANDARDS_VER.txt && \
	wget "https://github.com/Automattic/vip-go-ci/archive/$VIP_GO_CI_VER.tar.gz" && \
	tar -zxvf "$VIP_GO_CI_VER.tar.gz" && \
	mv "vip-go-ci-$VIP_GO_CI_VER" vip-go-ci && \
	rm -f "$VIP_GO_CI_VER.tar.gz" && \
	touch "$TMP_FOLDER/vip-go-ci-$VIP_GO_CI_VER.txt" && \
	mv $TMP_FOLDER ~/vip-go-ci-tools && \
	echo "Installation finished"
fi

