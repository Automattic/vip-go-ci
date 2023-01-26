#!/usr/bin/env php
<?php
/**
 * Command line utility that sends HTTP requests to the GitHub API
 * to create pull requests specified.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

define( 'VIPGOCI_INCLUDED', true );

// Extra long time to avoid secondary rate limits.
define( 'VIPGOCI_HTTP_API_WAIT_TIME_SECONDS', 10 );

/**
 * Ask the GitHub HTTP API to create pull requests specified.
 *
 * @param array $options         Options array for the program.
 * @param array $pr_items        Pull requests to create.
 * @param array $pr_items_failed Reference to array for pull requests not created.
 *
 * @return void
 */
function crprs_create_pull_requests(
	array $options,
	array $pr_items,
	array &$pr_items_failed
) :void {
	foreach ( $pr_items as $pr_item ) {
		vipgoci_log(
			'Creating pull request',
			array(
				'pr_item' => $pr_item,
			)
		);

		$ret = vipgoci_http_api_post_url(
			'https://api.github.com/repos/' .
				rawurlencode( $options['repo-owner'] ) . '/' .
				rawurlencode( $options['repo-name'] ) . '/' .
				'pulls',
			array(
				'title' => $pr_item['title'],
				'body'  => $pr_item['body'],
				'head'  => $pr_item['head'],
				'base'  => $pr_item['base'],
			),
			$options['github-token']
		);

		if ( -1 === $ret ) {
			vipgoci_log(
				'Failed to create pull request',
				array(
					'pr_item' => $pr_item,
				)
			);

			$pr_items_failed[] = $pr_item;
		} else {
			vipgoci_log(
				'Creation successful',
				array()
			);
		}
	}
}

/**
 * Main function.
 *
 * @return void
 */
function crprs_main() {
	global $argv;

	require_once __DIR__ . '/../requires.php';

	/*
	 * Configure PHP error reporting.
	 */
	vipgoci_set_php_error_reporting();

	/*
	 * Recognized options, get options.
	 */
	$options_recognized = array(
		'repo-owner:',
		'repo-name:',
		'github-token:',
		'pull-requests:',
		'env-options:',
	);

	$options = getopt(
		'',
		$options_recognized
	);

	/*
	 * Options to remove of any sensitive
	 * detail when cleaned later.
	 */
	vipgoci_options_sensitive_clean(
		null,
		array(
			'github-token',
		)
	);

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

	if (
		( ! isset(
			$options['repo-owner'],
			$options['repo-name'],
			$options['github-token'],
			$options['pull-requests'],
		) )
		||
		(
			isset( $options['help'] )
		)
	) {
		print 'Usage: ' . $argv[0] . PHP_EOL .
			PHP_EOL .
			"\t" . '--repo-owner=STRING            Specify repository owner, can be an organization.' . PHP_EOL .
			"\t" . '--repo-name=STRING             Specify name of the repository.' . PHP_EOL .
			"\t" . '--github-token=STRING          The access-token to use to communicate with GitHub.' . PHP_EOL .
			PHP_EOL .
			"\t" . '--pull-requests=STRING         Specify pull requests to create. Expects JSON format. For example:' . PHP_EOL .
			"\t" . '                               [{"title":"test branch","body":"Test pull request","head":"testing1","base":"main"},{...}]' . PHP_EOL .
			PHP_EOL .
			"\t" . '--env-options=STRING           Specifies configuration options to be read from environmental' . PHP_EOL .
			"\t" . '                               variables -- any variable can be specified. For example:' . PHP_EOL .
			"\t" . '                               --env-options="repo-owner=U_ROWNER,output=U_FOUTPUT"' . PHP_EOL .
			PHP_EOL .
			"\t" . '--help                         Prints this message.' . PHP_EOL .
			PHP_EOL .
			'All options, except --help, are mandatory.' . PHP_EOL;

		exit( VIPGOCI_EXIT_USAGE_ERROR );
	}

	$options['pull-requests'] = json_decode(
		$options['pull-requests'],
		true
	);

	$pr_items_failed  = array();
	$pr_items_failed2 = array();

	crprs_create_pull_requests(
		$options,
		$options['pull-requests'],
		$pr_items_failed
	);

	// Try failed items again.
	if ( ! empty( $pr_items_failed ) ) {
		vipgoci_log(
			'Retrying creation of pull requests that could not be created earlier',
			array(
				'pr_items_failed' => $pr_items_failed,
			)
		);

		sleep( 15 );

		crprs_create_pull_requests(
			$options,
			$pr_items_failed,
			$pr_items_failed2
		);
	}

	if ( ! empty( $pr_items_failed2 ) ) {
		vipgoci_log(
			'Failed creating pull requests, not retrying',
			array(
				'pr_items_failed' => $pr_items_failed2,
			)
		);
	} else {
		vipgoci_log(
			'Successfully created pull requests',
			array()
		);
	}

	exit( 0 );
}

crprs_main();

