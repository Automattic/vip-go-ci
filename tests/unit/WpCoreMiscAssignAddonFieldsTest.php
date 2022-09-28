<?php
/**
 * Test vipgoci_wpcore_misc_assign_addon_fields() function.
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
final class WpCoreMiscAssignAddonFieldsTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../wp-core-misc.php';
		require_once __DIR__ . '/helper/WpCoreMiscAssignAddonFields.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_assign_addon_fields
	 *
	 * @return void
	 */
	public function testAssignAddonFields(): void {
		$addons_found = array(
			'vipgoci-addon-plugin-plugin1.php' => array(
				'type'             => 'vipgoci-addon-plugin',
				'addon_headers'    => array(
					'Name' => 'My Plugin',
				),
				'name'             => 'My Plugin',
				'version_detected' => '1.0',
				'file_name'        => 'my-plugin/plugin.php',
			),
			'vipgoci-addon-theme-theme1.php'   => array(
				'type'             => 'vipgoci-addon-theme',
				'addon_headers'    => array(
					'Name' => 'Theme 1',
				),
				'name'             => 'Theme 1',
				'version_detected' => '1.1',
				'file_name'        => 'my-theme/style.css',
			),
			'vipgoci-addon-plugin-plugin2.php' => array(
				'type'             => 'vipgoci-addon-plugin',
				'addon_headers'    => array(
					'Name' => 'Other Plugin',
				),
				'name'             => 'Other Plugin',
				'version_detected' => '1.6',
				'file_name'        => 'my-plugin/plugin.php',
			),
		);

		$addons_details = array(
			'vipgoci-addon-plugin-plugin1.php' => array(
				'id'          => 'w.org/plugins/my-plugin',
				'slug'        => 'my-plugin',
				'plugin'      => 'plugin.php',
				'new_version' => '1.5',
				'package'     => 'https://downloads.wordpress.org/plugin/my-plugin.1.5.zip',
				'url'         => 'https://wordpress.org/plugins/my-plugin/',
			),
			'vipgoci-addon-theme-theme1.php'   => array(
				'slug'        => 'theme-1',
				'new_version' => '1.2',
				'package'     => 'https://downloads.wordpress.org/themes/theme-1.zip',
				'url'         => 'https://wordpress.org/themes/theme-1/',
			),
			'vipgoci-addon-plugin-plugin2.php' => array(
				// slug field missing, this plugin should not appear in resulting array.
				'new_version' => '1.7',
				'package'     => 'https://downloads.wordpress.org/plugin/my-plugin-1.7.zip',
				'url'         => 'https://wordpress.org/plugins/my-plugin/',
			),
		);

		$results_actual = vipgoci_wpcore_misc_assign_addon_fields(
			$addons_found,
			$addons_details
		);

		$this->assertSame(
			array(
				'vipgoci-addon-plugin-plugin1.php' => array(
					'type'             => 'vipgoci-addon-plugin',
					'addon_headers'    => array(
						'Name' => 'My Plugin',
					),
					'name'             => 'My Plugin',
					'version_detected' => '1.0',
					'file_name'        => 'my-plugin/plugin.php',
					'slug'             => 'my-plugin',
					'new_version'      => '1.5',
					'package'          => 'https://downloads.wordpress.org/plugin/my-plugin.1.5.zip',
					'url'              => 'https://wordpress.org/plugins/my-plugin/',
					'id'               => 'w.org/plugins/my-plugin',
					'plugin'           => 'plugin.php',
				),
				'vipgoci-addon-theme-theme1.php'   => array(
					'type'             => 'vipgoci-addon-theme',
					'addon_headers'    => array(
						'Name' => 'Theme 1',
					),
					'name'             => 'Theme 1',
					'version_detected' => '1.1',
					'file_name'        => 'my-theme/style.css',
					'slug'             => 'theme-1',
					'new_version'      => '1.2',
					'package'          => 'https://downloads.wordpress.org/themes/theme-1.zip',
					'url'              => 'https://wordpress.org/themes/theme-1/',
				),
			),
			$results_actual
		);
	}
}
