<?php
/**
 * Scan outcomes must not be process failures; failed execution must stay visible.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/** Cover completion, early skips, report failures, and operational failures. */
final class ScanOutcomeTest extends TestCase {
	/** Exercise real exit codes and report files, including replacement of stale data. */
	public function testProcessOutcomes(): void {
		foreach ( array(
			'clean'               => array( 0, 'clean' ),
			'warnings'            => array( 0, 'clean' ),
			'findings'            => array( 0, 'findings' ),
			'oversized'           => array( 0, 'findings' ),
			'no-pull-request'     => array( 0, 'no-pull-request' ),
			'superseded'          => array( 0, 'superseded' ),
			'disabled'            => array( 0, 'disabled' ),
			'empty-commits'       => array( 252, null ),
			'invalid-commit'      => array( 252, null ),
			'serialization-error' => array( 251, null ),
			'operational-error'   => array( 247, null ),
		) as $scenario => list( $expected_exit, $expected_outcome ) ) {
			$path = tempnam( sys_get_temp_dir(), 'vipgoci-outcome-' );
			try {
				file_put_contents( $path, 'stale report' );
				$output = array();
				exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/helper/ScanOutcome.php' ) . ' ' .
					escapeshellarg( $scenario ) . ' ' . escapeshellarg( $path ) . ' 2>&1', $output, $status );
				$this->assertSame( $expected_exit, $status, $scenario . ': ' . implode( "\n", $output ) );
				if ( null !== $expected_outcome ) {
					$report = json_decode( file_get_contents( $path ), true, 512, JSON_THROW_ON_ERROR );
					$this->assertSame( $expected_outcome, $report['scan-outcome'], $scenario );
					$this->assertSame( 'fixture', $report['repo-owner'] );
					$this->assertSame( str_repeat( 'a', 40 ), $report['commit'] );
					$this->assertIsArray( $report['results']['issues'] );
					$this->assertIsArray( $report['results']['stats'] );
					$this->assertIsArray( $report['prs_implicated'] );
				} else {
					$this->assertSame( '', file_get_contents( $path ), 'Old reports must be cleared before execution' );
				}
			} finally {
				unlink( $path );
			}
		}
	}

	/** An unwritable requested report is an execution failure. */
	public function testWriteFailure(): void {
		$output = array();
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/helper/ScanOutcome.php' ) .
			' write-failure ' . escapeshellarg( sys_get_temp_dir() ) . ' 2>&1', $output, $status );
		$this->assertSame( 251, $status, implode( "\n", $output ) );
	}

	/** The report remains optional for standalone callers. */
	public function testCompletionWithoutReport(): void {
		foreach ( array( 'clean', 'findings', 'no-pull-request', 'superseded', 'disabled' ) as $scenario ) {
			$output = array();
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/helper/ScanOutcome.php' ) .
				' ' . escapeshellarg( $scenario ) . " '' 2>&1", $output, $status );
			$this->assertSame( 0, $status, implode( "\n", $output ) );
		}
	}

	/** The real status publisher must propagate its HTTP transport failure. */
	public function testStatusPublicationFailure(): void {
		foreach ( array( 'success' => 0, 'failure' => 247 ) as $scenario => $expected_exit ) {
			$output = array();
			exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/helper/StatusPublication.php' ) .
				' ' . escapeshellarg( $scenario ) . ' 2>&1', $output, $status );
			$this->assertSame( $expected_exit, $status, implode( "\n", $output ) );
		}
	}
}
