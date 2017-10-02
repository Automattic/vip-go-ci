#!/bin/bash

if [ ! -d ~/vip-go-ci-tools ] ; then
	TMP_FOLDER=`mktemp -d /tmp/vip-go-ci-tools-XXXXXX`

	cd $TMP_FOLDER && \
	wget https://github.com/squizlabs/PHP_CodeSniffer/archive/2.8.0.tar.gz && \
	tar -zxvf 2.8.0.tar.gz  && \
	rm -fv 2.8.0.tar.gz && \
	mv PHP_CodeSniffer-2.8.0/ phpcs && \
	wget https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/0.11.0.tar.gz && \
	tar -zxvf 0.11.0.tar.gz  && \
	rm -fv 0.11.0.tar.gz && \
	mv WordPress-Coding-Standards-0.11.0/WordPress* phpcs/CodeSniffer/Standards/ && \
	git clone -b master https://github.com/Automattic/VIP-Coding-Standards.git VIP-Coding-Standards && \
	mv VIP-Coding-Standards/WordPressVIPMinimum/ phpcs/CodeSniffer/Standards/  && \
	git clone -b master https://github.com/Automattic/vip-go-ci.git && \
	mv $TMP_FOLDER ~/vip-go-ci-tools && \
	echo "Installation finished"
fi

