<?php
/**
 * Controlled PHPCS CLI output for PhpcsScanGitGuardsTest.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

$filenames = array_values( array_filter( array_slice( $argv, 1 ), 'is_file' ) );
$contents  = implode( "\n", array_map( 'file_get_contents', $filenames ) );
$log_path  = getenv( 'VIPGOCI_TEST_PHPCS_INVOCATIONS' );
if ( false !== $log_path ) {
	file_put_contents( $log_path, json_encode( array(
		'files' => $filenames,
		'argv'  => $argv,
	) ) . "\n", FILE_APPEND );
}

if ( count( $filenames ) > 1 && str_contains( $contents, 'fixture-batch-fatal' ) ) {
	fwrite( STDERR, 'Simulated PHPCS fatal error' );
	exit( 255 );
}

if ( str_contains( $contents, 'fixture-invalid-json' ) ) {
	echo 'Invalid scanner output';
	exit( 0 );
}

$report = array(
	'totals' => array(
		'errors'   => 0,
		'warnings' => 0,
		'fixable'  => 0,
	),
	'files'  => array(),
);
foreach ( $filenames as $filename ) {
	$file_contents = file_get_contents( $filename );
	if ( str_contains( $file_contents, 'fixture-empty-report' ) ) {
		continue;
	}
	$messages    = array();
	$error_count = 0;
	$warnings    = 0;
	foreach ( explode( "\n", $file_contents ) as $index => $line ) {
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
	if ( str_contains( $file_contents, 'fixture-missing-file' ) ) {
		$filename = '/unexpected.php';
	} elseif ( str_contains( $file_contents, 'fixture-leading-slash' ) ) {
		$filename = ltrim( $filename, '/' );
	}
	$report['files'][ $filename ]  = array(
		'errors'   => $error_count,
		'warnings' => $warnings,
		'messages' => $messages,
	);
	$report['totals']['errors']   += $error_count;
	$report['totals']['warnings'] += $warnings;
}
if ( count( $filenames ) > 1 ) {
	if ( str_contains( $contents, 'fixture-batch-missing' ) ) {
		array_pop( $report['files'] );
	} elseif ( str_contains( $contents, 'fixture-batch-invalid-entry' ) ) {
		$report['files'][ $filenames[0] ]['messages'] = null;
	} elseif ( str_contains( $contents, 'fixture-batch-count-mismatch' ) ) {
		++$report['totals']['errors'];
	} elseif ( str_contains( $contents, 'fixture-batch-unknown-file' ) ) {
		$report['files']['/unknown.php'] = $report['files'][ $filenames[0] ];
	}
}
echo json_encode( $report );
