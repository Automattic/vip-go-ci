<?php
/**
 * Controlled PHPCS CLI output for PhpcsScanGitGuardsTest.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

$filename = end( $argv );
$contents = file_get_contents( $filename );

if ( str_contains( $contents, 'fixture-invalid-json' ) ) {
	echo 'Invalid scanner output';
	exit( 0 );
}

$messages    = array();
$error_count = 0;
$warnings    = 0;
foreach ( explode( "\n", $contents ) as $index => $line ) {
	if ( ! preg_match( '/fixture-(error|warning)/', $line, $matches ) ) {
		continue;
	}
	$issue_type = strtoupper( $matches[1] );
	$messages[] = array(
		'message'  => 'Fixture finding.',
		'source'   => 'Fixture.Test.Finding',
		'severity' => 5,
		'fixable'  => false,
		'type'     => $issue_type,
		'line'     => $index + 1,
		'column'   => 1,
	);
	if ( 'ERROR' === $issue_type ) {
		++$error_count;
	} else {
		++$warnings;
	}
}

echo json_encode(
	array(
		'totals' => array(
			'errors'   => $error_count,
			'warnings' => $warnings,
			'fixable'  => 0,
		),
		'files'  => array(
			$filename => array(
				'errors'   => $error_count,
				'warnings' => $warnings,
				'messages' => $messages,
			),
		),
	)
);
