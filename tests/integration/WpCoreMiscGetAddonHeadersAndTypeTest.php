<?php
/**
 * Test vipgoci_wpcore_misc_get_addon_headers_and_type() function.
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
final class WpCoreMiscGetAddonHeadersAndTypeTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../wp-core-misc.php';
	}

	/**
	 * Test when a plugin should be detected.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_headers_and_type
	 *
	 * @return void
	 */
	public function testWpCoreMiscGetAddonHeadersAndTypeForPlugin(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name,
			'Unable to create temporary file'
		);

		$temp_file_name_new =
			$temp_file_name . '.php';

		$this->assertNotFalse(
			rename(
				$temp_file_name,
				$temp_file_name_new
			),
			'Unable to rename file'
		);

		$temp_file_name = $temp_file_name_new;

		unset( $temp_file_name_new );

		$this->assertNotFalse(
			file_put_contents(
				$temp_file_name,
				'<?php' . PHP_EOL .
				'/**' . PHP_EOL .
				' * @package MyPackage' . PHP_EOL .
				' * @version 1.0.0' . PHP_EOL .
				' */' . PHP_EOL .
				'/*' . PHP_EOL .
				'Plugin Name: My Package' . PHP_EOL .
				'Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
				'Description: My text.' . PHP_EOL .
				'Author: Author Name' . PHP_EOL .
				'Version: 1.0.0' . PHP_EOL .
				'Author URI: http://wordpress.org/author/test123' . PHP_EOL .
				'*/' . PHP_EOL,
				FILE_APPEND
			),
			'Unable to append to temporary file'
		);

		$expected_result = array(
			'type'             => 'vipgoci-wpscan-plugin',
			'addon_headers'    => array(
				'Name'        => 'My Package',
				'PluginURI'   => 'http://wordpress.org/test/my-package/',
				'Version'     => '1.0.0',
				'Description' => 'My text.',
				'Author'      => 'Author Name',
				'AuthorURI'   => 'http://wordpress.org/author/test123',
				'TextDomain'  => '',
				'DomainPath'  => '',
				'Network'     => '',
				'RequiresWP'  => '',
				'RequiresPHP' => '',
				'UpdateURI'   => '',
				'Title'       => 'My Package',
				'AuthorName'  => 'Author Name',
			),
			'name'             => 'My Package',
			'version_detected' => '1.0.0',
			'file_name'        => $temp_file_name,
		);

		$actual_result = vipgoci_wpcore_misc_get_addon_headers_and_type(
			$temp_file_name
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);
	}

	/**
	 * Test when a theme should be detected.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_headers_and_type
	 *
	 * @return void
	 */
	public function testWpCoreMiscGetAddonHeadersAndTypeForTheme(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name,
			'Unable to create temporary file'
		);

		$temp_file_name_new =
			$temp_file_name . '.css';

		$this->assertNotFalse(
			rename(
				$temp_file_name,
				$temp_file_name_new
			),
			'Unable to rename file'
		);

		$temp_file_name = $temp_file_name_new;

		unset( $temp_file_name_new );

		$this->assertNotFalse(
			file_put_contents(
				$temp_file_name,
				'/**' . PHP_EOL .
				' * @package MyPackage' . PHP_EOL .
				' * @version 1.0.0' . PHP_EOL .
				' */' . PHP_EOL .
				'/*' . PHP_EOL .
				'Theme Name: My Package' . PHP_EOL .
				'Theme URI: http://wordpress.org/test/my-package/' . PHP_EOL .
				'Description: My text.' . PHP_EOL .
				'Author: Author Name' . PHP_EOL .
				'Version: 1.0.0' . PHP_EOL .
				'Author URI: http://wordpress.org/author/test123' . PHP_EOL .
				'*/' . PHP_EOL,
				FILE_APPEND
			),
			'Unable to append to temporary file'
		);

		$expected_result = array(
			'type'             => 'vipgoci-wpscan-theme',
			'addon_headers'    => array(
				'Name'        => 'My Package',
				'ThemeURI'    => 'http://wordpress.org/test/my-package/',
				'Description' => 'My text.',
				'Author'      => 'Author Name',
				'AuthorURI'   => 'http://wordpress.org/author/test123',
				'Version'     => '1.0.0',
				'Template'    => '',
				'Status'      => '',
				'TextDomain'  => '',
				'DomainPath'  => '',
				'RequiresWP'  => '',
				'RequiresPHP' => '',
				'UpdateURI'   => '',
				'Title'       => 'My Package',
				'AuthorName'  => 'Author Name',
			),
			'name'             => 'My Package',
			'version_detected' => '1.0.0',
			'file_name'        => $temp_file_name,
		);

		$actual_result = vipgoci_wpcore_misc_get_addon_headers_and_type(
			$temp_file_name
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);
	}

	/**
	 * Test when nothing should be detected.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_addon_headers_and_type
	 *
	 * @return void
	 */
	public function testWpCoreMiscGetAddonHeadersAndTypeForInvalid(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name,
			'Unable to create temporary file'
		);

		$temp_file_name_new =
			$temp_file_name . '.txt';

		$this->assertNotFalse(
			rename(
				$temp_file_name,
				$temp_file_name_new
			),
			'Unable to rename file'
		);

		$temp_file_name = $temp_file_name_new;

		unset( $temp_file_name_new );

		$this->assertNotFalse(
			file_put_contents(
				$temp_file_name,
				'/**' . PHP_EOL .
				' * @package MyPackage' . PHP_EOL .
				' * @version 1.0.0' . PHP_EOL .
				' */' . PHP_EOL .
				'/*' . PHP_EOL .
				'Theme Name: My Package' . PHP_EOL .
				'Theme URI: http://wordpress.org/test/my-package/' . PHP_EOL .
				'Description: My text.' . PHP_EOL .
				'Author: Author Name' . PHP_EOL .
				'Version: 1.0.0' . PHP_EOL .
				'Author URI: http://wordpress.org/author/test123' . PHP_EOL .
				'*/' . PHP_EOL,
				FILE_APPEND
			),
			'Unable to append to temporary file'
		);

		$expected_result = null;

		$actual_result = vipgoci_wpcore_misc_get_addon_headers_and_type(
			$temp_file_name
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);
	}
}
