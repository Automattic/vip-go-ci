<?php
/**
 * Test vipgoci_wpcore_misc_determine_local_slug() function.
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
final class WpCoreMiscDetermineLocalSlugTest extends TestCase {
	/**
	 * Path to git repository (does not need to exist).
	 *
	 * @var $repo_path
	 */
	private string $repo_path = '/tmp/git-repo-123';

	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../wp-core-misc.php';
	}

	/**
	 * Data provider for testDetermineLocalSlug()
	 *
	 * @return array Data.
	 */
	public function dataDetermineLocalSlug() :array {
		require_once __DIR__ . '/../../defines.php';

		$slug_prefix = 'vipgoci-addon-';

		return array(
			array( $slug_prefix . 'theme-test-theme1', VIPGOCI_ADDON_THEME, $this->repo_path . '/themes/test-theme1/style.css' ),
			array( $slug_prefix . 'theme-test-theme2', VIPGOCI_ADDON_THEME, $this->repo_path . '/test-group/themes/test-theme2/style.css' ),
			array( $slug_prefix . 'theme-test-theme3', VIPGOCI_ADDON_THEME, $this->repo_path . '/plugins/test-plugin/test-theme3/style.css' ),

			array( $slug_prefix . 'plugin-hello-dolly/hello1.php', VIPGOCI_ADDON_PLUGIN, $this->repo_path . '/plugins/hello-dolly/hello1.php' ),
			array( $slug_prefix . 'plugin-hello-dolly/hello2.php', VIPGOCI_ADDON_PLUGIN, $this->repo_path . '/themes/test-theme4/plugins/hello-dolly/hello2.php' ),
			array( $slug_prefix . 'plugin-hello3.php', VIPGOCI_ADDON_PLUGIN, $this->repo_path . '/themes/test-theme5/plugins/hello3.php' ),
			array( $slug_prefix . 'plugin-hello4.php', VIPGOCI_ADDON_PLUGIN, $this->repo_path . '/hello4.php' ),
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @param string $expected_local_slug Expected "local" slug.
	 * @param string $input_addon_type    Add-on type.
	 * @param string $input_full_path     Full path to file.
	 *
	 * @dataProvider dataDetermineLocalSlug
	 *
	 * @covers ::vipgoci_wpcore_misc_determine_local_slug
	 *
	 * @return void
	 */
	public function testDetermineLocalSlug(
		string $expected_local_slug,
		string $input_addon_type,
		string $input_full_path,
	): void {
		$input_relative_path = str_replace(
			$this->repo_path . '/',
			'',
			$input_full_path
		);

		$this->assertSame(
			$expected_local_slug,
			vipgoci_wpcore_misc_determine_local_slug(
				$input_addon_type,
				$input_full_path,
				$input_relative_path
			)
		);
	}
}
