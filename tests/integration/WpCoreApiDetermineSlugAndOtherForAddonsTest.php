<?php
/**
 * Test vipgoci_wpcore_api_determine_slug_and_other_for_addons() function.
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
final class WpCoreApiDetermineSlugAndOtherForAddonsTest extends TestCase {
	/**
	 * Prefix for array keys indicating plugin.
	 *
	 * @var KEY_PLUGIN_PREFIX
	 */
	private const KEY_PLUGIN_PREFIX = 'vipgoci-addon-plugin';

	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test common usage of the function with plugins.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testCommonPluginUsage(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				self::KEY_PLUGIN_PREFIX . '-hello/hello.php'   => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
						'UpdateURI'   => 'http://wordpress.org/plugins/hello-dolly/',
					),
				),
				self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php' => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
						'UpdateURI'   => 'http://wordpress.org/plugins/hello-dolly/',
					),
				),
				self::KEY_PLUGIN_PREFIX . '-hello3/hello3.php' => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
						'UpdateURI'   => 'http://INVALID.INVALID/plugins/hello-dolly/',  // Non-WordPress.org plugin.
					),
				),
				self::KEY_PLUGIN_PREFIX . '-hello4/hello4.php' => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
						'UpdateURI'   => 'false', // Non-WordPress.org plugin.
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Ensure only hello/hello.php and hello2/hello2.php
		 * are returned in results.
		 */
		$this->assertSame(
			array(
				self::KEY_PLUGIN_PREFIX . '-hello/hello.php',
				self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php',
			),
			array_keys( $actual_results )
		);

		/*
		 * Test hello/hello.php
		 */

		$this->assertNotEmpty(
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]
		);

		$this->assertSame(
			'w.org/plugins/hello-dolly',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['id']
		);

		$this->assertSame(
			'hello-dolly',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['slug']
		);

		$this->assertSame(
			'hello/hello.php',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['plugin']
		);

		$this->assertTrue(
			version_compare(
				$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['new_version'],
				'0.0.0',
				'>='
			)
		);

		$this->assertStringContainsString(
			'/plugins/hello-dolly',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['url']
		);

		$this->assertStringContainsString(
			'/hello-dolly.',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['package']
		);

		/*
		 * Test hello2/hello2.php
		 */
		$this->assertTrue(
			isset( $actual_results[ self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php' ]['plugin'] )
		);

		$this->assertSame(
			'hello2/hello2.php',
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php' ]['plugin']
		);

		unset( $actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ]['plugin'] );
		unset( $actual_results[ self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php' ]['plugin'] );

		$this->assertSame(
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello/hello.php' ],
			$actual_results[ self::KEY_PLUGIN_PREFIX . '-hello2/hello2.php' ]
		);
	}

	/**
	 * Test when no results are expected with plugin data.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testPluginNoResults(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				self::KEY_PLUGIN_PREFIX . '-my-test/invalid.php' => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'This is invalid, 123',
						'PluginURI'   => 'http://wordpress.org/INVALID/invalid-1234/',
						'Version'     => '999.0',
						'Description' => 'This is invalid',
						'Author'      => 'No author',
						'AuthorURI'   => 'http://wordpress.org',
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array( self::KEY_PLUGIN_PREFIX . '-my-test/invalid.php' => null ),
			$actual_results,
		);
	}

	/**
	 * Test invalid usage with plugin data.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testInvalidPluginUsage1(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array()
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$actual_results,
		);
	}


	/**
	 * Test invalid usage with plugin data.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testInvalidPluginUsage2(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				self::KEY_PLUGIN_PREFIX . '-my-test/invalid.php'  => array(),
				self::KEY_PLUGIN_PREFIX . '-my-test/invalid2.php' => array(
					'type'          => 'vipgoci-addon-plugin',
					'addon_headers' => array(
						'Name'        => 'This is invalid, 123',
						'PluginURI'   => 'http://wordpress.org/INVALID/invalid-1234/',
						'Version'     => '999.0',
						'Description' => 'This is invalid',
						'Author'      => 'No author',
						'AuthorURI'   => 'http://wordpress.org',
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				self::KEY_PLUGIN_PREFIX . '-my-test/invalid2.php' => null,
			),
			$actual_results,
		);
	}

}
