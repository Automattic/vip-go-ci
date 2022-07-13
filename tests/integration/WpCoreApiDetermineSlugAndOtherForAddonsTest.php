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
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testCommonUsage(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				'hello/hello.php' => array(
					'type'          => 'vipgoci-wpscan-plugin',
					'addon_headers' => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
						'Update URI'  => 'http://wordpress.org/plugins/hello-dolly/',
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$actual_results['hello/hello.php']
		);

		$this->assertSame(
			'w.org/plugins/hello-dolly',
			$actual_results['hello/hello.php']['id']
		);

		$this->assertSame(
			'hello-dolly',
			$actual_results['hello/hello.php']['slug']
		);

		$this->assertSame(
			'hello/hello.php',
			$actual_results['hello/hello.php']['plugin']
		);

		$this->assertTrue(
			version_compare(
				$actual_results['hello/hello.php']['new_version'],
				'0.0.0',
				'>='
			)
		);

		$this->assertStringContainsString(
			'/plugins/hello-dolly',
			$actual_results['hello/hello.php']['url']
		);

		$this->assertStringContainsString(
			'/hello-dolly.',
			$actual_results['hello/hello.php']['package']
		);
	}

	/**
	 * Test when no results are expected.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testNoResults(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				'my-test/invalid.php' => array(
					'type'          => 'vipgoci-wpscan-plugin',
					'addon_headers' => array(
						'Name'        => 'This is invalid, 123',
						'PluginURI'   => 'http://wordpress.org/INVALID/invalid-1234/',
						'Version'     => '999.0',
						'Description' => 'This is invalid',
						'Author'      => 'No author',
						'AuthorURI '  => 'http://wordpress.org',
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array( 'my-test/invalid.php' => null ),
			$actual_results,
		);
	}

	/**
	 * Test invalid usage.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testInvalidUsage1(): void {
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
	 * Test invalid usage.
	 *
	 * @covers ::vipgoci_wpcore_api_determine_slug_and_other_for_addons
	 *
	 * @return void
	 */
	public function testInvalidUsage2(): void {
		vipgoci_unittests_output_suppress();

		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				'my-test/invalid.php'  => array(),
				'my-test/invalid2.php' => array(
					'type'          => 'vipgoci-wpscan-plugin',
					'addon_headers' => array(
						'Name'        => 'This is invalid, 123',
						'PluginURI'   => 'http://wordpress.org/INVALID/invalid-1234/',
						'Version'     => '999.0',
						'Description' => 'This is invalid',
						'Author'      => 'No author',
						'AuthorURI '  => 'http://wordpress.org',
					),
				),
			),
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'my-test/invalid2.php' => null,
			),
			$actual_results,
		);
	}

}
