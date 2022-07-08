<?php
/**
 * Test vipgoci_wpcore_misc_get_file_wp_headers() function.
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
final class WpCoreMiscGetFileWpHeadersTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../wp-core-misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_file_wp_headers
	 *
	 * @return void
	 */
	public function testGetWpHeadersValidHeaders1(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name
		);

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
				'*/' . PHP_EOL
			)
		);

		$expected_result = array(
			'Name'        => 'My Package',
			'PluginURI'   => 'http://wordpress.org/test/my-package/',
			'Version'     => '1.0.0',
			'Description' => 'My text.',
			'Author'      => 'Author Name',
			'AuthorURI'   => 'http://wordpress.org/author/test123',
			'Title'       => 'My Package',
			'AuthorName'  => 'Author Name',
		);

		$actual_result = vipgoci_wpcore_misc_get_file_wp_headers(
			$temp_file_name,
			array(
				'Name'        => 'Plugin Name',
				'PluginURI'   => 'Plugin URI',
				'Version'     => 'Version',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
			)
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);			
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_file_wp_headers
	 *
	 * @return void
	 */
	public function testGetWpHeadersValidHeaders2(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name
		);

		$this->assertNotFalse(
			file_put_contents(
				$temp_file_name,
				'<?php' . PHP_EOL .
				'/**' . PHP_EOL .
				' * @package MyPackage' . PHP_EOL .
				' * @version 1.0.0' . PHP_EOL .
				' */' . PHP_EOL .
				'/*' . PHP_EOL .
				' * Plugin Name: My Package' . PHP_EOL .
				' * Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
				' * Description: My text.' . PHP_EOL .
				' * Author: Author Name' . PHP_EOL .
				' * Version: 1.0.0' . PHP_EOL .
				' * Author URI: http://wordpress.org/author/test123' . PHP_EOL .
				' */' . PHP_EOL
			)
		);

		$expected_result = array(
			'Name'        => 'My Package',
			'PluginURI'   => 'http://wordpress.org/test/my-package/',
			'Version'     => '1.0.0',
			'Description' => 'My text.',
			'Author'      => 'Author Name',
			'AuthorURI'   => 'http://wordpress.org/author/test123',
			'Title'       => 'My Package',
			'AuthorName'  => 'Author Name',
		);

		$actual_result = vipgoci_wpcore_misc_get_file_wp_headers(
			$temp_file_name,
			array(
				'Name'        => 'Plugin Name',
				'PluginURI'   => 'Plugin URI',
				'Version'     => 'Version',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
			)
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);			
	}

	/**
	 * Test with invalid and missing headers.
	 *
	 * @covers ::vipgoci_wpcore_misc_get_file_wp_headers
	 *
	 * @return void
	 */
	public function testGetWpHeadersValidHeaders3(): void {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-wp-header-file'
		);

		$this->assertNotFalse(
			$temp_file_name
		);

		$this->assertNotFalse(
			file_put_contents(
				$temp_file_name,
				'<?php' . PHP_EOL .
				'/**' . PHP_EOL .
				' * @package MyPackage' . PHP_EOL .
				' * @version 1.0.0' . PHP_EOL .
				' */' . PHP_EOL .
				'/*' . PHP_EOL .
				' * Na me: My Package' . PHP_EOL .
				' * URI: http://wordpress.org/test/my-package/' . PHP_EOL .
				' * Text: My text.' . PHP_EOL .
				' */' . PHP_EOL
			)
		);

		$expected_result = array(
			'Name'        => '',
			'PluginURI'   => '',
			'Version'     => '',
			'Description' => '',
			'Author'      => '',
			'AuthorURI'   => '',
			'Title'       => '',
			'AuthorName'  => '',
		);

		$actual_result = vipgoci_wpcore_misc_get_file_wp_headers(
			$temp_file_name,
			array(
				'Name'        => 'Plugin Name',
				'PluginURI'   => 'Plugin URI',
				'Version'     => 'Version',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
			)
		);

		unlink( $temp_file_name );

		$this->assertSame(
			$expected_result,
			$actual_result
		);			
	}
}
