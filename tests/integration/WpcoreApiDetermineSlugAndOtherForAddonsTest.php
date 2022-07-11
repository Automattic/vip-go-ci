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
final class WpcoreApiDetermineSlugAndOtherForAddonsTest extends TestCase {
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
	public function testDetermineSlugAndOther(): void {
		$actual_results = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
			array(
				'hello/hello.php' => array(
					'type'             => 'vipgoci-wpscan-plugin',
					'addon_headers'    => array(
						'Name'        => 'Hello Dolly',
						'PluginURI'   => 'http://wordpress.org/plugins/hello-dolly/',
						'Version'     => '1.6',
						'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
						'Author'      => 'Matt Mullenweg',
						'AuthorURI '  => 'http://ma.tt/',
						'Title'       => 'Hello Dolly',
						'AuthorName'  => 'Matt Mullenweg',
					),
				),
			),
		);

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
}
