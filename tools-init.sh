#!/bin/bash

export PHP_CODENSIFFER_VER="3.1.0"
export WP_CODING_STANDARDS_VER="0.14.0"
export VIP_CODING_STANDARDS_VER="0.2.1"

if [ ! -d ~/vip-go-ci-tools ] ; then
	TMP_FOLDER=`mktemp -d /tmp/vip-go-ci-tools-XXXXXX`

	cd $TMP_FOLDER && \
	wget "https://github.com/squizlabs/PHP_CodeSniffer/archive/$PHP_CODENSIFFER_VER.tar.gz" && \
	tar -zxvf "$PHP_CODENSIFFER_VER.tar.gz"  && \
	rm -fv "$PHP_CODENSIFFER_VER.tar.gz" && \
	mv "PHP_CodeSniffer-$PHP_CODENSIFFER_VER/" phpcs && \
	wget "https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/$WP_CODING_STANDARDS_VER.tar.gz" && \
	tar -zxvf "$WP_CODING_STANDARDS_VER.tar.gz"  && \
	rm -fv "$WP_CODING_STANDARDS_VER.tar.gz" && \
	mv WordPress-Coding-Standards-$WP_CODING_STANDARDS_VER/WordPress* phpcs/src/Standards/ && \
	wget "https://github.com/Automattic/VIP-Coding-Standards/archive/$VIP_CODING_STANDARDS_VER.tar.gz" && \
	tar -zxvf "$VIP_CODING_STANDARDS_VER.tar.gz" && \
	mv "VIP-Coding-Standards-$VIP_CODING_STANDARDS_VER/WordPressVIPMinimum/" phpcs/src/Standards/  && \
	rm -f "$VIP_CODING_STANDARDS_VER".tar.gz && \
	wget https://github.com/Automattic/vip-go-ci/archive/master.tar.gz && \
	tar -zxvf master.tar.gz && \
	mv vip-go-ci-master vip-go-ci && \
	rm -f master.tar.gz && \
	mv $TMP_FOLDER ~/vip-go-ci-tools && \
	echo "Installation finished"
fi

