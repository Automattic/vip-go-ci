#!/bin/bash

if [ ! -d ~/vip-go-ci-tools ] ; then
	TMP_FOLDER=`mktemp -d /tmp/vip-go-ci-tools-XXXXXX`

	cd $TMP_FOLDER && \
	wget https://github.com/squizlabs/PHP_CodeSniffer/archive/3.1.0.tar.gz && \
	tar -zxvf 3.1.0.tar.gz  && \
	rm -fv 3.1.0.tar.gz && \
	mv PHP_CodeSniffer-3.1.0/ phpcs && \
	wget https://github.com/WordPress-Coding-Standards/WordPress-Coding-Standards/archive/0.14.0.tar.gz && \
	tar -zxvf 0.14.0.tar.gz  && \
	rm -fv 0.14.0.tar.gz && \
	mv WordPress-Coding-Standards-0.14.0/WordPress* phpcs/src/Standards/ && \
	wget https://github.com/Automattic/VIP-Coding-Standards/archive/master.tar.gz && \
	tar -zxvf master.tar.gz && \
	mv VIP-Coding-Standards-master/WordPressVIPMinimum/ phpcs/src/Standards/  && \
	rm -f master.tar.gz && \
	wget https://github.com/Automattic/vip-go-ci/archive/master.tar.gz && \
	tar -zxvf master.tar.gz && \
	mv vip-go-ci-master vip-go-ci && \
	rm -f master.tar.gz && \
	mv $TMP_FOLDER ~/vip-go-ci-tools && \
	echo "Installation finished"
fi

