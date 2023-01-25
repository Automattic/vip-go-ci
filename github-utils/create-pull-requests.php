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

/**
 * Ask the GitHub HTTP API to create pull requests specified.
 *
 * @return void
 */
function cpr_create_pull_requests(
	array $options,
	array $pr_items,
	array &$pr_items_failed
) :void {
	foreach ( $pr_items as $pr_item ) {
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
		}

		$pr_items_failed[] = $pr_item;

		// Try to avoid secondary rate limit errors.
		sleep( 5 );
	}
}

/**
 * Main function.
 *
 * @return void
 */
function cpr_main() {
	require_once __DIR__ . '/../requires.php';

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

	if ( empty( $options['env-options'] ) ) {
		echo 'Missing --env-options' . PHP_EOL;
		exit( 1 );
	}

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
		( empty( $options['repo-owner'] ) ) ||
		( empty( $options['repo-name'] ) ) ||
		( empty( $options['github-token'] ) ) ||
		( empty( $options['pull-requests'] ) )
	) {
		echo 'Missing options' . PHP_EOL;
		exit( 1 );
	}

	$options['pull-requests'] = json_decode(
		$options['pull-requests'],
		true
	);

	$pr_items_failed = array();

	cpr_create_pull_requests(
		$options,
		$options['pull-requests'],
		$pr_items_failed
	);

	// Try failed items again.
	if ( ! empty( $pr_items_failed ) ) {
		sleep( 10 );

		$pr_items_failed2 = array();

		cpr_create_pull_requests(
			$options,
			$pr_items_failed,
			$pr_items_failed2
		);
	}

	exit( 0 );
}

cpr_main();

