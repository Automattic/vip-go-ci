<?php
/**
 * Test function vipgoci_option_skip_folder_handle().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test if *-skip-folders variables are correctly set.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OptionsSkipFolderHandleTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set variable.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../options.php';
		$this->options = array();
	}

	/**
	 * Clear variable.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test if --phpcs-skip-folders and --lint-skip-folders
	 * are correctly parsed.
	 *
	 * @covers ::vipgoci_option_skip_folder_handle
	 *
	 * @return void
	 */
	public function testOptionSkipFolderHandle1() :void {
		$this->options['phpcs-skip-folders'] =
			' var/tmp/,/client-mu-plugins/myplugin/ ,/plugins/myplugin/, /tmp/1,tmp/3 ';

		$this->options['lint-skip-folders'] =
			'var/tmp2/ ,/client-mu-plugins/otherplugin/,/plugins/otherplugin/, /tmp/2,tmp/4';

		vipgoci_option_skip_folder_handle(
			$this->options,
			'phpcs-skip-folders'
		);

		vipgoci_option_skip_folder_handle(
			$this->options,
			'lint-skip-folders'
		);

		$this->assertSame(
			array(
				'phpcs-skip-folders' => array(
					'var/tmp',
					'client-mu-plugins/myplugin',
					'plugins/myplugin',
					'tmp/1',
					'tmp/3',
				),
				'lint-skip-folders'  => array(
					'var/tmp2',
					'client-mu-plugins/otherplugin',
					'plugins/otherplugin',
					'tmp/2',
					'tmp/4',
				),
			),
			$this->options
		);
	}
}
