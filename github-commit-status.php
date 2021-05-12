#!/usr/bin/env php
<?php

// phpcs:disable PSR1.Files.SideEffects

define( 'VIPGOCI_INCLUDED', true );

require_once( __DIR__ . '/requires.php' );

vipgoci_log(
	'Preparing to set GitHub build status'
);

/*
 * Configure PHP error reporting.
 */
vipgoci_set_php_error_reporting();

/*
 * Recognized options, get options
 */
$options_recognized = array(
	'env-options:',
	'repo-owner:',
	'repo-name:',
	'github-token:',
	'github-commit:',
	'build-state:',
	'build-description:',
	'build-context:',
);

$options = getopt(
	null,
	$options_recognized
);

/*
 * Options to remove of any sensitive
 * detail when cleaned later.
 */
vipgoci_options_sensitive_clean(
	null,
	array(
		'github-token'
	)
);


/*
 * Parse options
 */
vipgoci_option_array_handle(
	$options,
	'env-options',
	array(),
	null,
	',',
	false
);

vipgoci_options_read_env(
	$options,
	$options_recognized
);

/*
 * Verify we have all the options
 * we need.
 */
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

/*
 * Verify that --build-state is of valid
 * value.
 */
switch( $options['build-state'] ) {
	case 'pending':
	case 'failure':
	case 'success':
		break;

	default:
		vipgoci_sysexit(
			'Invalid parameter for --build-state, only "pending", "failure", and "success" are valid',
			array(
				'build-state'	=> $options['build-state'],
			)
		);
}

/*
 * Log that we are setting build
 * status and set it.
 */
vipgoci_log(
	'Setting build status for commit ...',
	array(
		'options' => vipgoci_options_sensitive_clean(
			$options
		)
	)
);

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

vipgoci_log(
	'Finished, exiting',
);
