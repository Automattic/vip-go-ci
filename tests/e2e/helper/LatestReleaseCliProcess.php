<?php
/**
 * Run the release CLI and retain its complete output streams.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\E2E;

use RuntimeException;

/**
 * Test-only subprocess wrapper for latest-release.php.
 */
final class LatestReleaseCliProcess {
	/**
	 * Execute the real release CLI.
	 *
	 * @param string|null $bootstrap Optional auto-prepend bootstrap.
	 * @param array       $arguments Additional CLI arguments for a fixture bootstrap.
	 * @return array{stdout: string, stderr: string, status: int} Process result.
	 */
	public static function run( ?string $bootstrap = null, array $arguments = array() ): array {
		$command = array( PHP_BINARY );
		if ( null !== $bootstrap ) {
			$command[] = '-d';
			$command[] = 'auto_prepend_file=' . $bootstrap;
		}
		$command[] = dirname( __DIR__, 3 ) . '/latest-release.php';
		$command   = array_merge( $command, $arguments );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.system_calls_proc_open -- The CLI contract requires a real subprocess.
		$process = proc_open(
			$command,
			array(
				0 => array( 'pipe', 'r' ),
				1 => array( 'pipe', 'w' ),
				2 => array( 'pipe', 'w' ),
			),
			$pipes
		);
		if ( ! is_resource( $process ) ) {
			throw new RuntimeException( 'Unable to start latest-release.php.' );
		}

		fclose( $pipes[0] );
		$stdout = stream_get_contents( $pipes[1] );
		fclose( $pipes[1] );
		$stderr = stream_get_contents( $pipes[2] );
		fclose( $pipes[2] );
		$status = proc_close( $process );

		if ( false === $stdout || false === $stderr ) {
			throw new RuntimeException( 'Unable to read latest-release.php output.' );
		}

		return array(
			'stdout' => $stdout,
			'stderr' => $stderr,
			'status' => $status,
		);
	}
}
