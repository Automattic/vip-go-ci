#!/bin/bash

set -e

#
# Before updating these version numbers and
# hashes, please read the documentation here:
# https://github.com/Automattic/vip-go-ci/#updating-tools-initsh-with-new-versions
#

# https://github.com/squizlabs/PHP_CodeSniffer
export PHP_CODESNIFFER_REPO="squizlabs/PHP_CodeSniffer"
export PHP_CODESNIFFER_VER="3.7.1"
export PHP_CODESNIFFER_VER_FILE="php-codesniffer-$PHP_CODESNIFFER_VER.txt"
export PHP_CODESNIFFER_SHA1SUM="ca1bd8bc97fede23155b83029d174079c3905014"

# https://github.com/WordPress/WordPress-Coding-Standards
export WP_CODING_STANDARDS_REPO="WordPress/WordPress-Coding-Standards"
export WP_CODING_STANDARDS_VER="2.3.0"
export WP_CODING_STANDARDS_VER_FILE="wp-coding-standards-$WP_CODING_STANDARDS_VER.txt"
export WP_CODING_STANDARDS_SHA1SUM="c8161d77fcf63bdeaa3e8e6aa36bc1936b469070"

# https://github.com/automattic/vip-coding-standards
export VIP_CODING_STANDARDS_REPO="automattic/vip-coding-standards"
export VIP_CODING_STANDARDS_VER="2.3.3"
export VIP_CODING_STANDARDS_VER_FILE="vip-coding-standards-$VIP_CODING_STANDARDS_VER.txt"
export VIP_CODING_STANDARDS_SHA1SUM="44c6519c628d450be5330b2706ae9dbb09dbd6be"

# https://github.com/sirbrillig/phpcs-variable-analysis
export PHPCS_VARIABLE_ANALYSIS_REPO="sirbrillig/phpcs-variable-analysis"
export PHPCS_VARIABLE_ANALYSIS_VER="v2.11.9"
export PHPCS_VARIABLE_ANALYSIS_VER_FILE="phpcs-variable-analysis-$PHPCS_VARIABLE_ANALYSIS_VER.txt"
export PHPCS_VARIABLE_ANALYSIS_SHA1SUM="42bd1fd822e0326ef39522098a8ae3f5fb65146a"

# https://github.com/phpcompatibility/phpcompatibility
export PHP_COMPATIBILITY_REPO="phpcompatibility/phpcompatibility"
export PHP_COMPATIBILITY_VER="c23e20c0aaa5c527fd7b3fbef38c50c458bb47f1" # Using develop branch.
export PHP_COMPATIBILITY_VER_FILE="php-compatibility-$PHP_COMPATIBILITY_VER.txt"
export PHP_COMPATIBILITY_SHA1SUM="3787a3f86edbfbc4881d2b123f17ca1e1bcbeb66"

# https://github.com/phpcompatibility/phpcompatibilitywp
export PHP_COMPATIBILITY_WP_REPO="phpcompatibility/phpcompatibilitywp"
export PHP_COMPATIBILITY_WP_VER="2.1.3"
export PHP_COMPATIBILITY_WP_VER_FILE="php-compatibility-wp-$PHP_COMPATIBILITY_WP_VER.txt"
export PHP_COMPATIBILITY_WP_SHA1SUM="671eb42d7a20008e9259755a72c28c94c0d04e9f"

# https://github.com/phpcompatibility/phpcompatibilityparagonie
export PHP_COMPATIBILITY_PARAGONIE_REPO="phpcompatibility/phpcompatibilityparagonie"
export PHP_COMPATIBILITY_PARAGONIE_VER="1.3.1"
export PHP_COMPATIBILITY_PARAGONIE_VER_FILE="php-compatibility-paragonie-$PHP_COMPATIBILITY_PARAGONIE_VER.txt"
export PHP_COMPATIBILITY_PARAGONIE_SHA1SUM="a51cf3a1af05e6192ce9db6fc90ccb7afd58cb22"

# https://github.com/PHPCSStandards/PHPCSUtils
export PHPCS_UTILS_REPO="PHPCSStandards/PHPCSUtils"
export PHPCS_UTILS_VER="b65fbd47c38202a667ea3930a4a51c5c8b9ca434" # Using develop branch.
export PHPCS_UTILS_VER_FILE="phpcs-utils-$PHPCS_UTILS_VER.txt"
export PHPCS_UTILS_SHA1SUM="bc3309c56747e4c2ee165fa4b1ba2bf994e78570"

# https://github.com/Automattic/vip-go-svg-sanitizer
export VIP_GO_SVG_SANITIZER_REPO="Automattic/vip-go-svg-sanitizer"
export VIP_GO_SVG_SANITIZER_VER="0.9.8"
export VIP_GO_SVG_SANITIZER_VER_FILE="vip-go-svg-sanitizer-$VIP_GO_SVG_SANITIZER_VER.txt"
export VIP_GO_SVG_SANITIZER_SHA1SUM="558f16dcff6adc4637c1d0287cc6f95fe9ab2ece"

export TMP_LOCK_FILE="$HOME/.vip-go-ci-tools-init.lck"

function sha1sum_check() {
	FILENAME=$1
	CORRECT_HASH=$2

	TMP_HASH=`sha1sum $FILENAME|awk '{print $1}'`

	if [ "$TMP_HASH" != "$CORRECT_HASH" ] ; then
		echo "FAILED sha1sum check for $FILENAME; $TMP_HASH (downloaded) vs. $CORRECT_HASH (correct)"
		return 1
	fi

	return 0
}

function gh_fetch_and_verify() {
	GITHUB_OWNER_AND_REPO=$1
	VERSION_TO_FETCH=$2
	VERSION_INDICATOR_FILE=$3
	CORRECT_HASH=$4
	FILES_TO_MOVE=$5
	DESTINATION_DIR=$6

	TMP_FOR_ARCHIVE=`mktemp -d /tmp/vip-go-ci-tools-archive-XXXXXX`

	( pushd $TMP_FOR_ARCHIVE && \
	wget -O "archive.tar.gz" "https://github.com/$GITHUB_OWNER_AND_REPO/archive/$VERSION_TO_FETCH.tar.gz" && \
	sha1sum_check "archive.tar.gz" "$CORRECT_HASH" && \
	tar -zxf "archive.tar.gz" && \
	mv $FILES_TO_MOVE $DESTINATION_DIR && \
	touch $VERSION_INDICATOR_FILE && \
	rm -rf $TMP_FOR_ARCHIVE && \
	popd && \
	echo "$0: Fetched & verified for $GITHUB_OWNER_AND_REPO" && \
	return 0 ) \
	|| \
	( echo "$0: Problem fetching/verifying files for $GITHUB_OWNER_AND_REPO" ; \
	rm -rf "$TMP_FOR_ARCHIVE" ; \
	return 1 )
}

function lock_place() {
	# Get lock, if that fails, just exit
	if [ -f "$TMP_LOCK_FILE" ] ; then
		echo "$0: Lock in place already, not doing anything."
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
		echo "$0: Will not check for updates at this time, exiting"
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
	wget -O "$TMP_FILE" https://raw.githubusercontent.com/Automattic/vip-go-ci/latest/latest-release.php && \
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


	for TMP_FILE in	"$PHP_CODESNIFFER_VER_FILE" "$WP_CODING_STANDARDS_VER_FILE" "$VIP_CODING_STANDARDS_VER_FILE" "$PHPCS_VARIABLE_ANALYSIS_VER_FILE" "$PHP_COMPATIBILITY_VER_FILE" "$PHP_COMPATIBILITY_WP_VER_FILE" "$PHP_COMPATIBILITY_PARAGONIE_VER_FILE" "$PHPCS_UTILS_VER_FILE" "$VIP_GO_SVG_SANITIZER_VER_FILE" "vip-go-ci-$VIP_GO_CI_VER.txt"; do
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

	cd $TMP_FOLDER || exit "Unable to change to dir $TMP_FOLDER"
	
	gh_fetch_and_verify "$PHP_CODESNIFFER_REPO" $PHP_CODESNIFFER_VER "$TMP_FOLDER/$PHP_CODESNIFFER_VER_FILE" $PHP_CODESNIFFER_SHA1SUM "PHP_CodeSniffer-$PHP_CODESNIFFER_VER/" "$TMP_FOLDER/phpcs" && \
	\
	gh_fetch_and_verify "$WP_CODING_STANDARDS_REPO" $WP_CODING_STANDARDS_VER "$TMP_FOLDER/$WP_CODING_STANDARDS_VER_FILE" $WP_CODING_STANDARDS_SHA1SUM "WordPress-Coding-Standards-$WP_CODING_STANDARDS_VER/WordPress*" $TMP_FOLDER/phpcs/src/Standards/ && \
	\
	gh_fetch_and_verify "$VIP_CODING_STANDARDS_REPO" "$VIP_CODING_STANDARDS_VER" "$TMP_FOLDER/$VIP_CODING_STANDARDS_VER_FILE" $VIP_CODING_STANDARDS_SHA1SUM "VIP-Coding-Standards-$VIP_CODING_STANDARDS_VER/WordPressVIPMinimum/ VIP-Coding-Standards-$VIP_CODING_STANDARDS_VER/WordPress-VIP-Go/" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$PHPCS_VARIABLE_ANALYSIS_REPO" "$PHPCS_VARIABLE_ANALYSIS_VER" "$TMP_FOLDER/$PHPCS_VARIABLE_ANALYSIS_VER_FILE" "$PHPCS_VARIABLE_ANALYSIS_SHA1SUM" "phpcs-variable-analysis-*/VariableAnalysis/" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$PHP_COMPATIBILITY_REPO" "$PHP_COMPATIBILITY_VER" "$TMP_FOLDER/$PHP_COMPATIBILITY_VER_FILE" "$PHP_COMPATIBILITY_SHA1SUM" "PHPCompatibility-$PHP_COMPATIBILITY_VER/PHPCompatibility PHPCompatibility-$PHP_COMPATIBILITY_VER/PHPCSAliases.php" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$PHP_COMPATIBILITY_WP_REPO" "$PHP_COMPATIBILITY_WP_VER" "$TMP_FOLDER/$PHP_COMPATIBILITY_WP_VER_FILE" "$PHP_COMPATIBILITY_WP_SHA1SUM" "PHPCompatibilityWP-$PHP_COMPATIBILITY_WP_VER/PHPCompatibilityWP" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$PHP_COMPATIBILITY_PARAGONIE_REPO" "$PHP_COMPATIBILITY_PARAGONIE_VER" "$TMP_FOLDER/$PHP_COMPATIBILITY_PARAGONIE_VER_FILE" "$PHP_COMPATIBILITY_PARAGONIE_SHA1SUM" "PHPCompatibilityParagonie-$PHP_COMPATIBILITY_PARAGONIE_VER/PHPCompatibilityParagonie*" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$PHPCS_UTILS_REPO" "$PHPCS_UTILS_VER" "$TMP_FOLDER/$PHPCS_UTILS_VER_FILE" "$PHPCS_UTILS_SHA1SUM" "PHPCSUtils-$PHPCS_UTILS_VER/PHPCS* PHPCSUtils-$PHPCS_UTILS_VER/phpcsutils-autoload.php" "$TMP_FOLDER/phpcs/src/Standards/" && \
	\
	gh_fetch_and_verify "$VIP_GO_SVG_SANITIZER_REPO" "$VIP_GO_SVG_SANITIZER_VER" "$TMP_FOLDER/$VIP_GO_SVG_SANITIZER_VER_FILE" "$VIP_GO_SVG_SANITIZER_SHA1SUM" "vip-go-svg-sanitizer-$VIP_GO_SVG_SANITIZER_VER" "$TMP_FOLDER/vip-go-svg-sanitizer" && \
	\
	( wget "https://github.com/Automattic/vip-go-ci/archive/$VIP_GO_CI_VER.tar.gz" && \
	tar -zxf "$VIP_GO_CI_VER.tar.gz" && \
	mv "vip-go-ci-$VIP_GO_CI_VER" vip-go-ci && \
	rm -f "$VIP_GO_CI_VER.tar.gz" && \
	touch "$TMP_FOLDER/vip-go-ci-$VIP_GO_CI_VER.txt" && \
	echo "$0: Installation of tools finished" && \
	mv $TMP_FOLDER ~/vip-go-ci-tools ) \
	|| \
	( echo "$0: Unable to install tools" ; \
	rm -rf "$TMP_FOLDER" ; \
	lock_remove ; \
	exit 1 )
fi

lock_remove
