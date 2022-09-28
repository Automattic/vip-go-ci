<?php
/**
 * Test vipgoci_wpcore_misc_cleanup_header_comment() function.
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
final class WpCoreMiscCleanUpHeaderCommentTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../wp-core-misc.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_cleanup_header_comment
	 *
	 * @return void
	 */
	public function testWpCoreMiscCleanUpHeaderCommentTest1(): void {
		$header_str_original = '<?php' . PHP_EOL .
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
			'*/' . PHP_EOL;

		$header_str_expected = '<?php' . PHP_EOL .
			'/**' . PHP_EOL .
			' * @package MyPackage' . PHP_EOL .
			' * @version 1.0.0' . PHP_EOL .
			'/*' . PHP_EOL .
			'Plugin Name: My Package' . PHP_EOL .
			'Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
			'Description: My text.' . PHP_EOL .
			'Author: Author Name' . PHP_EOL .
			'Version: 1.0.0' . PHP_EOL .
			'Author URI: http://wordpress.org/author/test123';

		$this->assertSame(
			$header_str_expected,
			vipgoci_wpcore_misc_cleanup_header_comment( $header_str_original )
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_cleanup_header_comment
	 *
	 * @return void
	 */
	public function testWpCoreMiscCleanUpHeaderCommentTest2(): void {
		$header_str_original = '<?php' . PHP_EOL .
			'/*' . PHP_EOL .
			'Plugin Name: My Package' . PHP_EOL .
			'Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
			'Description: My text.' . PHP_EOL .
			'Author: Author Name' . PHP_EOL .
			'Version: 1.0.0' . PHP_EOL .
			'Author URI: http://wordpress.org/author/test123' . PHP_EOL .
			'*/' . PHP_EOL .
			'test, test' . PHP_EOL;

		$header_str_expected = '<?php' . PHP_EOL .
			'/*' . PHP_EOL .
			'Plugin Name: My Package' . PHP_EOL .
			'Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
			'Description: My text.' . PHP_EOL .
			'Author: Author Name' . PHP_EOL .
			'Version: 1.0.0' . PHP_EOL .
			'Author URI: http://wordpress.org/author/test123' . PHP_EOL .
			'test, test';

		$this->assertSame(
			$header_str_expected,
			vipgoci_wpcore_misc_cleanup_header_comment( $header_str_original )
		);
	}


	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_wpcore_misc_cleanup_header_comment
	 *
	 * @return void
	 */
	public function testWpCoreMiscCleanUpHeaderCommentTest3(): void {
		$header_str_original = '<?php' . PHP_EOL .
			'/*' . PHP_EOL .
			' * Plugin Name: My Package' . PHP_EOL .
			' * Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
			' * Description: My text.' . PHP_EOL .
			' * Author: Author Name' . PHP_EOL .
			' * Version: 1.0.0' . PHP_EOL .
			' * Author URI: http://wordpress.org/author/test123' . PHP_EOL .
			' */' . PHP_EOL;

		$header_str_expected = '<?php' . PHP_EOL .
			'/*' . PHP_EOL .
			' * Plugin Name: My Package' . PHP_EOL .
			' * Plugin URI: http://wordpress.org/test/my-package/' . PHP_EOL .
			' * Description: My text.' . PHP_EOL .
			' * Author: Author Name' . PHP_EOL .
			' * Version: 1.0.0' . PHP_EOL .
			' * Author URI: http://wordpress.org/author/test123';

		$this->assertSame(
			$header_str_expected,
			vipgoci_wpcore_misc_cleanup_header_comment( $header_str_original )
		);
	}


}
