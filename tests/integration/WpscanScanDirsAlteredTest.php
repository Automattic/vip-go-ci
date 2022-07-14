<?php
/**
 * Test vipgoci_wpscan_scan_dirs_altered() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WpscanScanDirsAlteredTest extends TestCase {
	/**
	 * Variable for WPScan API configuration.
	 *
	 * @var $options_wpscan_api_scan
	 */
	private array $options_wpscan_api_scan = array(
		'wpscan-pr-1-commit-id' => null,
		'wpscan-pr-1-dirs-scan' => null,
	);

	/**
	 * Variable for git setup.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	/**
	 * Setup function. Require files, prepare repository, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'wpscan-api-scan',
			$this->options_wpscan_api_scan
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = array_merge(
			$this->options_wpscan_api_scan,
			$this->options_git
		);

		$this->options['commit'] =
			$this->options['wpscan-pr-1-commit-id'];

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-1-dirs-scan']
		);

		$this->options['wpscan-api-url'] = VIPGOCI_WPSCAN_API_BASE_URL;

		$this->options['wpscan-api-skip-folders'] = array();

		$this->options['wpscan-api-token'] =
			vipgoci_unittests_get_config_value(
				'wpscan-api-scan',
				'access-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['wpscan-api-token'] ) ) {
			$this->options['wpscan-api-token'] = '';
		}
	}

	/**
	 * Tear down function. Remove variables and temporary repository.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_wpscan_api_scan );
		unset( $this->options_git );
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpscan_scan_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAltered(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			array(
				'plugins',
				'plugins/hello',
				'plugins/not-a-plugin',
			)
		);

		$this->assertSame(
			array(
				'plugins/hello',
			),
			array_keys( $results_actual )
		);

		$this->assertSame(
			array(
				'hello.php',
			),
			array_keys( $results_actual['plugins/hello'] )
		);

		$this->assertSame(
			array(
				'security_type',
				'wpscan_results',
				'addon_data_for_dir',
			),
			array_keys( $results_actual['plugins/hello']['hello.php'] )
		);

		$this->assertTrue(
			isset( $results_actual['plugins/hello']['hello.php']['security_type'] )
		);

		$this->assertTrue(
			isset( $results_actual['plugins/hello']['hello.php']['wpscan_results']['friendly_name'] )
		);

		$this->assertTrue(
			isset( $results_actual['plugins/hello']['hello.php']['wpscan_results']['latest_version'] )
		);

		$this->assertTrue(
			isset( $results_actual['plugins/hello']['hello.php']['wpscan_results']['vulnerabilities'] )
		);

		$this->assertFalse(
			empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir'] )
		);

		$this->assertFalse(
			empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['type'] )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['id'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['id'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['slug'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['slug'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['new_version'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['new_version'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['plugin'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['plugin'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['package'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['package'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['url'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['url'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['addon_headers']['Name'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['addon_headers']['Name'] ) )
		);

		$this->assertTrue(
			( isset( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['addon_headers']['AuthorName'] ) ) &&
			( ! empty( $results_actual['plugins/hello']['hello.php']['addon_data_for_dir']['addon_headers']['AuthorName'] ) )
		);
	}
}


