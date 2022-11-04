<?php
/**
 * Test vipgoci_wpcore_misc_get_addons_not_altered() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class WpCoreMiscGetAddonsNotAlteredTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../wp-core-misc.php';
		require_once __DIR__ . '/../../misc.php';
	}

	/**
	 * Tests common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addons_not_altered
	 *
	 * @return void
	 */
	public function testGetAddonsNotAltered(): void {
		$options = array(
			'wpscan-api-paths' => array(
				'plugins', 'themes', 'client-mu-plugins',
			),
		);

		$known_addons = array(
			'client-mu-plugins/my-plugin/plugin.php',
			'client-mu-plugins/my-plugin/path1/test.php',
			'client-mu-plugins/my-plugin/path1/test2.php',
			'client-mu-plugins/my-plugin2/plugin.php',
			'plugins/hello/hello.php',
			'plugins/hello2/hello2.php',
			'plugins/hello3/hello3.php',
			'themes/theme1/hello/hello.php',
			'themes/theme1/style.css',
			'themes/theme2/hello/hello.php',
			'themes/theme2/style.css',
			'themes/theme3/hello/hello.php',
			'themes/theme3/style.css',
		);

		$files_affected_by_commit_by_pr = array(
			'all' => array(
				'client-mu-plugins/my-plugin/plugin.php',
				'client-mu-plugins/my-plugin/path1/test.php',
				'client-mu-plugins/my-plugin/path1/test2.php',
				'plugins/hello/hello.php',
				'plugins/hello/docs/file.php',
				'plugins/hello/docs/file.txt',
 				'plugins/hello2/hello2.php',
				'testing/test-plugin.php',
				'themes/theme1/hello/hello.php',
				'themes/theme2/style.css',
			)
		);

		$addons_not_altered = vipgoci_wpcore_misc_get_addons_not_altered(
			$options,
			$known_addons,
			$files_affected_by_commit_by_pr
		);

		$this->assertSame(
			array(
				'client-mu-plugins/my-plugin2/plugin.php',
				'plugins/hello3/hello3.php',
				'themes/theme1/style.css',
				'themes/theme2/hello/hello.php',
				'themes/theme3/hello/hello.php',
				'themes/theme3/style.css',
			),
			$addons_not_altered
		);
	}
}
