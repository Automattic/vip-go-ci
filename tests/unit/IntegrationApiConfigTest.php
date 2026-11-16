<?php
/**
 * Verify offline configuration ignores credentials and enables fake writes.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Exercise mode selection without reading real credentials or making requests.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class IntegrationApiConfigTest extends TestCase {
	/**
	 * Real configuration is used only when live mode is explicitly requested.
	 *
	 * @return void
	 */
	public function testCredentialsAndWriteControlsFollowTheSelectedMode(): void {
		$directory = tempnam( sys_get_temp_dir(), 'vipgoci-config-' );
		$this->assertNotFalse( $directory );
		unlink( $directory );
		mkdir( $directory );
		$previous_mode = getenv( 'VIPGOCI_TESTING_LIVE_API' );
		try {
			file_put_contents( $directory . '/config-secrets.ini', "[git-secrets]\ngithub-token=live-example-token\ngithub-skip-write-tests=true\n" );
			define( 'VIPGOCI_UNIT_TESTS_INI_DIR_PATH', $directory );
			require_once __DIR__ . '/../integration/IncludesForTestsConfig.php';
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Exercise mode selection.
			putenv( 'VIPGOCI_TESTING_LIVE_API=false' );
			$this->assertSame( 'fixture-token', vipgoci_unittests_get_config_value( 'git-secrets', 'github-token', true ) );
			$this->assertFalse( vipgoci_unittests_get_skip_github_write_tests() );

			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Exercise mode selection.
			putenv( 'VIPGOCI_TESTING_LIVE_API=true' );
			$this->assertSame( 'live-example-token', vipgoci_unittests_get_config_value( 'git-secrets', 'github-token', true ) );
			$this->assertTrue( vipgoci_unittests_get_skip_github_write_tests() );
		} finally {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Exercise mode selection.
			putenv( false === $previous_mode ? 'VIPGOCI_TESTING_LIVE_API' : 'VIPGOCI_TESTING_LIVE_API=' . $previous_mode );
			unlink( $directory . '/config-secrets.ini' );
			rmdir( $directory );
		}
	}
}
