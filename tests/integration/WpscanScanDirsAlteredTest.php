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
		'wpscan-pr-1-commit-id'    => null,
		'wpscan-pr-1-dirs-scan'    => null,
		'wpscan-pr-1-dirs-altered' => null,
		'wpscan-pr-1-plugin-dir'   => null,
		'wpscan-pr-1-plugin-key'   => null,
		'wpscan-pr-1-plugin-name'  => null,
		'wpscan-pr-1-plugin-slug'  => null,
		'wpscan-pr-1-plugin-path'  => null,
		'wpscan-pr-1-theme-dir'    => null,
		'wpscan-pr-1-theme-key'    => null,
		'wpscan-pr-1-theme-name'   => null,
		'wpscan-pr-1-theme-slug'   => null,
		'wpscan-pr-1-theme-path'   => null,
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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['commit'] =
			$this->options['wpscan-pr-1-commit-id'];

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
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			)
		);

		$this->assertSame(
			array(
				$this->options['wpscan-pr-1-plugin-dir'],
				$this->options['wpscan-pr-1-theme-dir'],
			),
			array_keys( $results_actual )
		);

		foreach ( array( 'plugin', 'theme' ) as $addon_type ) {
			$this->assertSame(
				array(
					$this->options[ 'wpscan-pr-1-' . $addon_type . '-key' ],
				),
				array_keys( $results_actual[ $this->options[ 'wpscan-pr-1-' . $addon_type . '-dir' ] ] )
			);

			$addon_details = $results_actual[ $this->options[ 'wpscan-pr-1-' . $addon_type . '-dir' ] ][ $this->options[ 'wpscan-pr-1-' . $addon_type . '-key' ] ];

			$this->assertSame(
				array(
					'security_type',
					'wpscan_results',
					'addon_data_for_dir',
				),
				array_keys( $addon_details )
			);

			$this->assertTrue(
				isset( $addon_details['security_type'] )
			);

			$this->assertTrue(
				isset( $addon_details['wpscan_results']['friendly_name'] )
			);

			$this->assertTrue(
				isset( $addon_details['wpscan_results']['latest_version'] )
			);

			$this->assertTrue(
				isset( $addon_details['wpscan_results']['vulnerabilities'] )
			);

			$this->assertFalse(
				empty( $addon_details['addon_data_for_dir'] )
			);

			$this->assertFalse(
				empty( $addon_details['addon_data_for_dir']['type'] )
			);

			if ( 'plugin' === $addon_type ) {
				$this->assertTrue(
					( isset( $addon_details['addon_data_for_dir']['id'] ) ) &&
					( ! empty( $addon_details['addon_data_for_dir']['id'] ) )
				);
			}

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['slug'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['slug'] ) )
			);

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['new_version'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['new_version'] ) )
			);

			if ( 'plugin' === $addon_type ) {
				$this->assertTrue(
					( isset( $addon_details['addon_data_for_dir']['plugin'] ) ) &&
					( ! empty( $addon_details['addon_data_for_dir']['plugin'] ) )
				);
			}

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['package'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['package'] ) )
			);

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['url'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['url'] ) )
			);

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['addon_headers']['Name'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['addon_headers']['Name'] ) )
			);

			$this->assertSame(
				$this->options[ 'wpscan-pr-1-' . $addon_type . '-name' ],
				$addon_details['addon_data_for_dir']['addon_headers']['Name']
			);

			$this->assertTrue(
				( isset( $addon_details['addon_data_for_dir']['addon_headers']['AuthorName'] ) ) &&
				( ! empty( $addon_details['addon_data_for_dir']['addon_headers']['AuthorName'] ) )
			);
		}
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

		/*
		 * Update version numbers in plugin/theme files in
		 * repository so they match the latest ones.
		 */
		$this->updateAddonVersionNumbers( true );

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			)
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

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Update version numbers in plugin/theme files in
		 * repository so they are higher than the latest ones.
		 */
		$this->updateAddonVersionNumbers( false );

		$results_actual = vipgoci_wpscan_scan_dirs_altered(
			$this->options,
			explode(
				',',
				$this->options['wpscan-pr-1-dirs-altered']
			)
		);

		$this->assertSame(
			array(),
			$results_actual
		);
	}

	/**
	 * Update version numbers of addons in repository so they match
	 * the latest ones or are higher than the latest ones. Will ask
	 * WPScan API for the latest version numbers if applicable.
	 *
	 * @param bool $latest If to set to the latest version (true) or higher (false).
	 *
	 * @return void
	 */
	private function updateAddonVersionNumbers(
		bool $latest = true
	) :void {
		if ( true === $latest ) {
			$wpscan_plugin_info = vipgoci_wpscan_do_scan_via_api(
				$this->options['wpscan-pr-1-plugin-slug'],
				VIPGOCI_ADDON_PLUGIN,
				$this->options['wpscan-api-token']
			);

			$wpscan_plugin_version =
				$wpscan_plugin_info[ $this->options['wpscan-pr-1-plugin-slug'] ]['latest_version'];
		} else {
			$wpscan_plugin_version = '100.0';
		}

		$this->replaceVersionNumberForFile(
			$this->options['local-git-repo'] . DIRECTORY_SEPARATOR .
				$this->options['wpscan-pr-1-plugin-path'],
			vipgoci_output_sanitize_version_number(
				$wpscan_plugin_version
			)
		);

		if ( true === $latest ) {
			$wpscan_theme_info = vipgoci_wpscan_do_scan_via_api(
				$this->options['wpscan-pr-1-theme-slug'],
				VIPGOCI_ADDON_THEME,
				$this->options['wpscan-api-token']
			);

			$wpscan_theme_version =
				$wpscan_theme_info[ $this->options['wpscan-pr-1-theme-slug'] ]['latest_version'];
		} else {
			$wpscan_theme_version = '100.0';
		}

		$this->replaceVersionNumberForFile(
			$this->options['local-git-repo'] . DIRECTORY_SEPARATOR .
				$this->options['wpscan-pr-1-theme-path'],
			vipgoci_output_sanitize_version_number(
				$wpscan_theme_version
			)
		);
	}

	/**
	 * Replace version number header in given file.
	 *
	 * @param string $file_path      Path to file.
	 * @param string $version_number Version number.
	 *
	 * @return void
	 */
	private function replaceVersionNumberForFile(
		string $file_path,
		string $version_number
	) :void {
		$file_contents = file_get_contents(
			$file_path
		);

		if ( false === $file_contents ) {
			return;
		}

		$file_contents = preg_replace(
			'/Version: .*/',
			'Version: ' . $version_number,
			$file_contents
		);

		file_put_contents(
			$file_path,
			$file_contents
		);
	}
}
