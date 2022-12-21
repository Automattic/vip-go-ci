<?php
/**
 * Test vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory() function.
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
final class WpCoreMiscGetAddonDataAndSlugsForDirectoryTest extends TestCase {
	/**
	 * Temporary directory.
	 *
	 * @var $temp_dir
	 */
	private $temp_dir = '';

	/**
	 * Constant for Hello plugin.
	 *
	 * @var $KEY_PLUGIN_HELLO
	 */
	private const KEY_PLUGIN_HELLO = 'vipgoci-addon-plugin-hello/hello.php';

	/**
	 * Constant for this-is-a-plugin.
	 *
	 * @var $KEY_PLUGIN_THIS_IS
	 */
	private const KEY_PLUGIN_THIS_IS = 'vipgoci-addon-plugin-this-is-a-plugin.php';

	/**
	 * Constant for twentytwentyone.
	 *
	 * @var $KEY_THEME_TWENTYTWENTYONE
	 */
	private const KEY_THEME_TWENTYTWENTYONE = 'vipgoci-addon-theme-twentytwentyone';

	/**
	 * Variable for WPScan API scanning.
	 *
	 * @var $options_wpscan_api_scan
	 */
	private array $options_wpscan_api_scan = array(
		'wpscan-pr-1-plugin-name'     => null,
		'wpscan-pr-1-plugin-slug'     => null,
		'wpscan-pr-1-plugin-name-api' => null,
		'wpscan-pr-1-theme-name'      => null,
		'wpscan-pr-1-theme-slug'      => null,
	);

	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'wpscan-api-scan',
			$this->options_wpscan_api_scan
		);

		$this->temp_dir = sys_get_temp_dir() .
			'/directory_for_addons-' .
			hash( 'sha256', random_bytes( 2048 ) );

		if ( true !== mkdir( $this->temp_dir ) ) {
			echo 'Unable to create temporary directory.';

			$this->temp_dir = '';
		}
	}

	/**
	 * Tear down function. Clean up temporary files.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		if ( ! empty( $this->temp_dir ) ) {
			if ( false === exec(
				escapeshellcmd( 'rm' ) .
				' -rf ' .
				escapeshellarg( $this->temp_dir )
			) ) {
				echo 'Unable to remove temporary directory' . PHP_EOL;
			}
		}
	}

	/**
	 * Test common usage of the function. Scans subdirectories.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory
	 *
	 * @return void
	 */
	public function testGetAddonDataAndSlugsForDirectoryWithSubdirectories(): void {
		if ( empty( $this->temp_dir ) ) {
			$this->markTestSkipped(
				'Temporary directory not existing.'
			);

			return;
		}

		$cp_cmd = escapeshellcmd( 'cp' ) .
			' -R ' .
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest' ) .
			' ' .
			escapeshellarg( $this->temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to copy files'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$this->temp_dir . DIRECTORY_SEPARATOR . 'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest',
			'mu-plugins',
			array( 'php' ),
			array( 'css' ),
			true
		);
		vipgoci_unittests_output_unsuppress();

		/*
		 * Verify a few values separately and remove before
		 * main assertion.
		 */

		// For hello/hello.php.
		$this->assertStringContainsString(
			'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest/mu-plugins/hello/hello.php',
			$actual_results[ self::KEY_PLUGIN_HELLO ]['file_name']
		);

		unset( $actual_results[ self::KEY_PLUGIN_HELLO ]['file_name'] );

		$this->assertTrue(
			version_compare(
				$actual_results[ self::KEY_PLUGIN_HELLO ]['new_version'],
				'0.0.0',
				'>='
			)
		);

		unset( $actual_results[ self::KEY_PLUGIN_HELLO ]['new_version'] );

		$this->assertStringContainsString(
			'/hello-dolly.',
			$actual_results[ self::KEY_PLUGIN_HELLO ]['package']
		);

		unset( $actual_results[ self::KEY_PLUGIN_HELLO ]['package'] );

		// For this-is-a-plugin.php.
		foreach (
			array( 'id', 'slug', 'plugin', 'new_version', 'url', 'package' ) as $field_name
		) {
			$this->assertFalse(
				isset( $actual_results[ self::KEY_PLUGIN_THIS_IS ][ $field_name ] )
			);
		}

		$this->assertStringContainsString(
			'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest/mu-plugins/this-is-a-plugin.php',
			$actual_results[ self::KEY_PLUGIN_THIS_IS ]['file_name']
		);

		unset( $actual_results[ self::KEY_PLUGIN_THIS_IS ]['file_name'] );

		// For twentytwentyone.
		$this->assertTrue(
			version_compare(
				$actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['new_version'],
				'0.0.0',
				'>='
			)
		);

		unset( $actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['new_version'] );

		$this->assertStringContainsString(
			'/twentytwentyone.',
			$actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['package']
		);

		unset( $actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['package'] );

		$this->assertStringContainsString(
			'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest/mu-plugins/twentytwentyone/style.css',
			$actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['file_name']
		);

		unset( $actual_results[ self::KEY_THEME_TWENTYTWENTYONE ]['file_name'] );

		/*
		 * Perform main assertion
		 */
		$this->assertSame(
			array(
				self::KEY_PLUGIN_HELLO          => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.7.2',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI'   => 'http://ma.tt/',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => '',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
					),
					'name'             => $this->options_wpscan_api_scan['wpscan-pr-1-plugin-name'],
					'version_detected' => '1.7.2',
					'slug'             => $this->options_wpscan_api_scan['wpscan-pr-1-plugin-slug'],
					'url'              => 'https://wordpress.org/plugins/' . $this->options_wpscan_api_scan['wpscan-pr-1-plugin-slug'] . '/',
					'id'               => 'w.org/plugins/' . $this->options_wpscan_api_scan['wpscan-pr-1-plugin-slug'],
					'plugin'           => $this->options_wpscan_api_scan['wpscan-pr-1-plugin-name-api'],
				),
				self::KEY_PLUGIN_THIS_IS        => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name'        => 'This is a plugin.',
						'PluginURI'   => 'http://wordpress.org/test/my-other-package/',
						'Version'     => '15.1.0',
						'Description' => 'This is indeed <b>a plugin</b>..',
						'Author'      => 'Test author.',
						'AuthorURI'   => 'http://wordpress.org/author/test124',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => '',
						'Title'       => 'This is a plugin.',
						'AuthorName'  => 'Test author.',
					),
					'name'             => 'This is a plugin.',
					'version_detected' => '15.1.0',
				),
				self::KEY_THEME_TWENTYTWENTYONE => array(
					'type'             => 'vipgoci-addon-theme',
					'addon_headers'    => array(
						'Name'        => 'Twenty Twenty-One',
						'ThemeURI'    => 'https://wordpress.org/themes/twentytwentyone/',
						'Description' => 'Twenty Twenty-One is a blank canvas for your ideas and it makes the block editor your best brush. With new block patterns, which allow you to create a beautiful layout in a matter of seconds, this theme’s soft colors and eye-catching — yet timeless — design will let your work shine. Take it for a spin! See how Twenty Twenty-One elevates your portfolio, business website, or personal blog.',
						'Author'      => 'the WordPress team',
						'AuthorURI'   => 'https://wordpress.org/',
						'Version'     => '1.2',
						'Template'    => '',
						'Status'      => '',
						'TextDomain'  => 'twentytwentyone',
						'DomainPath'  => '',
						'RequiresWP'  => '5.3',
						'RequiresPHP' => '5.6',
						'UpdateURI'   => '',
						'Title'       => 'Twenty Twenty-One',
						'AuthorName'  => 'the WordPress team',
					),
					'name'             => $this->options_wpscan_api_scan['wpscan-pr-1-theme-name'],
					'version_detected' => '1.2',
					'slug'             => $this->options_wpscan_api_scan['wpscan-pr-1-theme-slug'],
					'url'              => 'https://wordpress.org/themes/' . $this->options_wpscan_api_scan['wpscan-pr-1-theme-slug'] . '/',
				),
			),
			$actual_results
		);
	}

	/**
	 * Test common usage of the function. Does not scan subdirectories.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory
	 *
	 * @return void
	 */
	public function testGetAddonDataAndSlugsForDirectorySkipSubdirectories(): void {
		if ( empty( $this->temp_dir ) ) {
			$this->markTestSkipped(
				'Temporary directory not existing.'
			);

			return;
		}

		$cp_cmd = escapeshellcmd( 'cp' ) .
			' -R ' .
			escapeshellarg( __DIR__ . '/helper-files/WpCoreMiscGetAddonDataAndSlugsForDirectoryTest' ) .
			' ' .
			escapeshellarg( $this->temp_dir );

		if ( false === exec( $cp_cmd ) ) {
			$this->markTestSkipped(
				'Unable to copy files'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$this->temp_dir . DIRECTORY_SEPARATOR . 'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest',
			'mu-plugins',
			array( 'php' ),
			array( 'css' ),
			false
		);

		vipgoci_unittests_output_unsuppress();

		// For this-is-a-plugin.php.
		foreach (
			array( 'id', 'slug', 'plugin', 'new_version', 'url', 'package' ) as $field_name
		) {
			$this->assertFalse(
				isset( $actual_results[ self::KEY_PLUGIN_THIS_IS ][ $field_name ] )
			);
		}

		$this->assertStringContainsString(
			'WpCoreMiscGetAddonDataAndSlugsForDirectoryTest/mu-plugins/this-is-a-plugin.php',
			$actual_results[ self::KEY_PLUGIN_THIS_IS ]['file_name']
		);

		unset( $actual_results[ self::KEY_PLUGIN_THIS_IS ]['file_name'] );

		/*
		 * Perform main assertion
		 */
		$this->assertSame(
			array(
				self::KEY_PLUGIN_THIS_IS => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name'        => 'This is a plugin.',
						'PluginURI'   => 'http://wordpress.org/test/my-other-package/',
						'Version'     => '15.1.0',
						'Description' => 'This is indeed <b>a plugin</b>..',
						'Author'      => 'Test author.',
						'AuthorURI'   => 'http://wordpress.org/author/test124',
						'TextDomain'  => '',
						'DomainPath'  => '',
						'Network'     => '',
						'RequiresWP'  => '',
						'RequiresPHP' => '',
						'UpdateURI'   => '',
						'Title'       => 'This is a plugin.',
						'AuthorName'  => 'Test author.',
					),
					'name'             => 'This is a plugin.',
					'version_detected' => '15.1.0',
				),
			),
			$actual_results
		);
	}
}
