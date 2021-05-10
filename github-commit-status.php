#!/usr/bin/env php
<?php

define( 'VIPGOCI_INCLUDED', true );

require_once( __DIR__ . '/requires.php' );

global $argv;

/*
 * Set how to deal with errors:
 * Report all errors, and display them.
 */
ini_set( 'error_log', '' );
        
error_reporting( E_ALL );
ini_set( 'display_errors', 'on' );

        
$options = getopt(
	null,
	array(
		'repo-owner:',
		'repo-name:',
		'github-token:',
		'github-commit:',
		'build-state:',
		'build-description:',
		'build-context:',
	)
);

if ( ! isset(
	$options['repo-owner'],
	$options['repo-name'],
	$options['github-token'],
	$options['github-commit'],
	$options['build-state'],
	$options['build-description'],
	$options['build-context']
) ) {
	vipgoci_sysexit(
		'Missing parameter'
	);
}

vipgoci_github_status_create(
	$options['repo-owner'],
	$options['repo-name'],
	$options['github-token'],
	$options['github-commit'],
	$options['build-state'],
	'',
	$options['build-description'],
	$options['build-context']
);

