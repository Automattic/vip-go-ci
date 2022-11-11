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
		'wpscan-pr-1-commit-id'      => null,
		'wpscan-pr-1-dirs-scan'      => null,
		'wpscan-pr-1-dirs-altered'   => null,
		'wpscan-pr-1-plugin-dir'     => null,
		'wpscan-pr-1-plugin-key'     => null,
		'wpscan-pr-1-plugin-name'    => null,
		'wpscan-pr-1-plugin-slug'    => null,
		'wpscan-pr-1-plugin-version' => null,
		'wpscan-pr-1-plugin-path'    => null,
		'wpscan-pr-1-theme-dir'      => null,
		'wpscan-pr-1-theme-key'      => null,
		'wpscan-pr-1-theme-name'     => null,
		'wpscan-pr-1-theme-slug'     => null,
		'wpscan-pr-1-theme-version'  => null,
		'wpscan-pr-1-theme-path'     => null,
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

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['wpscan-api-paths'] = explode(
			',',
			$this->options['wpscan-pr-1-dirs-scan']
		);

		$this->options['wpscan-api-url'] = VIPGOCI_WPSCAN_API_BASE_URL;

		$this->options['wpscan-api-skip-folders'] = array();

		$this->options['wpscan-api-plugin-file-extensions'] = array( 'php' );

		$this->options['wpscan-api-theme-file-extensions'] = array( 'css' );

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
	 * Test common usage of the function. Expect results.
	 *
	 * @covers ::vipgoci_wpscan_scan_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAlteredWithResultsFound(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		$addon_data_and_slugs_for_addon_dirs = array(
			$this->options['wpscan-pr-1-plugin-dir'] => array(
				'vipgoci-addon-plugin-' . $this->options['wpscan-pr-1-plugin-dir'] => array(
					'type'             => 'vipgoci-addon-plugin',
					'slug'             => $this->options['wpscan-pr-1-plugin-slug'],
					'version_detected' => $this->options['wpscan-pr-1-plugin-version'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
				),
			),
			'plugins/not-a-plugin'                   => array(),
			$this->options['wpscan-pr-1-theme-dir']  => array(
				'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-key'] => array(
					'type'             => 'vipgoci-addon-theme',
					'slug'             => $this->options['wpscan-pr-1-theme-slug'],
					'version_detected' => $this->options['wpscan-pr-1-theme-version'],
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
				),
			),
		);

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$addon_data_and_slugs_for_addon_dirs
		);

		$results_expected = array(
			$this->options['wpscan-pr-1-plugin-dir'] => array(
				$this->options['wpscan-pr-1-plugin-dir'] => array(
					'wpscan_results'     => array(
						'friendly_name'  => $this->options['wpscan-pr-1-plugin-name'],
						'latest_version' => $this->getAddonVersionNumber(
							$this->options['wpscan-pr-1-plugin-slug'],
							VIPGOCI_ADDON_PLUGIN
						),
					),
					'addon_data_for_dir' => array(
						'type'             => 'vipgoci-addon-plugin',
						'slug'             => $this->options['wpscan-pr-1-plugin-slug'],
						'version_detected' => $this->options['wpscan-pr-1-plugin-version'],
						'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
					),
				),
			),
			$this->options['wpscan-pr-1-theme-dir']  => array(
				$this->options['wpscan-pr-1-theme-slug'] => array(
					'wpscan_results'     => array(
						'friendly_name'  => $this->options['wpscan-pr-1-theme-name'],
						'latest_version' => $this->getAddonVersionNumber(
							$this->options['wpscan-pr-1-theme-slug'],
							VIPGOCI_ADDON_THEME
						),
					),
					'addon_data_for_dir' => array(
						'type'             => 'vipgoci-addon-theme',
						'slug'             => $this->options['wpscan-pr-1-theme-slug'],
						'version_detected' => $this->options['wpscan-pr-1-theme-version'],
						'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
					),
				),
			),
		);

		$this->assertIsString(
			$results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['security_type']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['security_type'] );

		$this->assertIsString(
			$results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['last_updated']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['last_updated'] );

		$this->assertIsArray(
			$results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['vulnerabilities']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['vulnerabilities'] );

		$this->assertIsString(
			$results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['security_type']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['security_type'] );

		$this->assertIsString(
			$results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['last_updated']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['last_updated'] );

		$this->assertIsArray(
			$results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['vulnerabilities']
		);

		unset( $results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['vulnerabilities'] );

		$this->assertSame(
			$results_expected,
			$results_actual
		);

		$this->assertTrue(
			isset( $results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['latest_version'] ) &&
			version_compare(
				$results_actual[ $this->options['wpscan-pr-1-plugin-dir'] ][ $this->options['wpscan-pr-1-plugin-dir'] ]['wpscan_results']['latest_version'],
				$this->options['wpscan-pr-1-plugin-version'],
				'>'
			)
		);

		$this->assertTrue(
			isset( $results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['latest_version'] ) &&
			version_compare(
				$results_actual[ $this->options['wpscan-pr-1-theme-dir'] ][ $this->options['wpscan-pr-1-theme-slug'] ]['wpscan_results']['latest_version'],
				$this->options['wpscan-pr-1-theme-version'],
				'>'
			)
		);
	}

	/**
	 * Test common usage of the function. No results expected
	 * as plugin and theme are of latest version.
	 *
	 * @covers ::vipgoci_wpscan_scan_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAlteredWithNoResultsFound1(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		/*
		 * Ensure version numbers are the latest.
		 */
		$addon_data_and_slugs_for_addon_dirs = array(
			$this->options['wpscan-pr-1-plugin-dir'] => array(
				'vipgoci-addon-plugin-' . $this->options['wpscan-pr-1-plugin-dir'] => array(
					'type'             => 'vipgoci-addon-plugin',
					'slug'             => $this->options['wpscan-pr-1-plugin-slug'],
					'version_detected' => $this->getAddonVersionNumber(
						$this->options['wpscan-pr-1-plugin-slug'],
						VIPGOCI_ADDON_THEME
					),
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
				),
			),
			'plugins/not-a-plugin'                   => array(),
			$this->options['wpscan-pr-1-theme-dir']  => array(
				'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-key'] => array(
					'type'             => 'vipgoci-addon-theme',
					'slug'             => $this->options['wpscan-pr-1-theme-slug'],
					'version_detected' => $this->getAddonVersionNumber(
						$this->options['wpscan-pr-1-theme-slug'],
						VIPGOCI_ADDON_THEME
					),
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
				),
			),
		);

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$addon_data_and_slugs_for_addon_dirs
		);

		$this->assertSame(
			array(),
			$results_actual
		);
	}

	/**
	 * Test common usage of the function. No results expected
	 * as plugin and theme are more recent than latest.
	 *
	 * @covers ::vipgoci_wpscan_scan_dirs_altered
	 *
	 * @return void
	 */
	public function testFindDirsAlteredWithNoResultsFound2(): void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		/*
		 * Version numbers for plugin and theme are higher
		 * than the latest available ones.
		 */
		$addon_data_and_slugs_for_addon_dirs = array(
			$this->options['wpscan-pr-1-plugin-dir'] => array(
				'vipgoci-addon-plugin-' . $this->options['wpscan-pr-1-plugin-dir'] => array(
					'type'             => 'vipgoci-addon-plugin',
					'slug'             => $this->options['wpscan-pr-1-plugin-slug'],
					'version_detected' => '100.0',
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-plugin-path'],
				),
			),
			'plugins/not-a-plugin'                   => array(),
			$this->options['wpscan-pr-1-theme-dir']  => array(
				'vipgoci-addon-theme-' . $this->options['wpscan-pr-1-theme-key'] => array(
					'type'             => 'vipgoci-addon-theme',
					'slug'             => $this->options['wpscan-pr-1-theme-slug'],
					'version_detected' => '100.0',
					'file_name'        => $this->options['local-git-repo'] . '/' . $this->options['wpscan-pr-1-theme-path'],
				),
			),
		);

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			),
			$addon_data_and_slugs_for_addon_dirs
		);

		$this->assertSame(
			array(),
			$results_actual
		);
	}

	/**
	 * Will ask the WPScan API for the latest version number of add-on.
	 *
	 * @param string $addon_slug Add-on slug.
	 * @param string $addon_type Add-on type.
	 *
	 * @return string Version number.
	 */
	private function getAddonVersionNumber(
		string $addon_slug,
		string $addon_type
	) :string {
		$wpscan_plugin_info = vipgoci_wpscan_do_scan_via_api(
			$addon_slug,
			$addon_type,
			$this->options['wpscan-api-token']
		);

		return (string) $wpscan_plugin_info[ $addon_slug ]['latest_version'];
	}
}
