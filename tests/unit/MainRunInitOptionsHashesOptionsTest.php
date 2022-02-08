<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Check if hashes-to-hashes options are handled correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsHashesOptionsTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array();
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Check if hashes-to-hashes default options are correctly
	 * parsed and provided.
	 *
	 * @covers ::vipgoci_run_init_options_hashes_options
	 */
	public function testRunInitOptionsHashesOptionsDefault() :void {
		$this->options = array(
			'hashes-api'          => null,
			'hashes-api-url'      => 'https://api.test.local/v1/api',
			'hashes-oauth-param1' => ' value1  ',
			'hashes-oauth-param2' => '  value2 ',
		);

		$this->hashes_oauth_arguments = array(
			'hashes-oauth-param1',
			'hashes-oauth-param2',
		);

		vipgoci_run_init_options_hashes_options(
			$this->options,
			$this->hashes_oauth_arguments
		);

		$this->assertSame(
			array(
				'hashes-api'          => false,
				'hashes-api-url'      => 'https://api.test.local/v1/api',
				'hashes-oauth-param1' => 'value1',
				'hashes-oauth-param2' => 'value2',
			),
			$this->options
		);
	}

	/**
	 * Check if hashes-to-hashes options are correctly parsed.
	 *
	 * @covers ::vipgoci_run_init_options_hashes_options
	 */
	public function testRunInitOptionsHashesOptionsCustom() :void {
		$this->options = array(
			'hashes-api'          => 'true',
			'hashes-api-url'      => 'https://api.test.local/v1/api',
			'hashes-oauth-param1' => ' value1  ',
			'hashes-oauth-param2' => '  value2 ',
		);

		$this->hashes_oauth_arguments = array(
			'hashes-oauth-param1',
			'hashes-oauth-param2',
		);

		vipgoci_run_init_options_hashes_options(
			$this->options,
			$this->hashes_oauth_arguments
		);

		$this->assertSame(
			array(
				'hashes-api'          => true,
				'hashes-api-url'      => 'https://api.test.local/v1/api',
				'hashes-oauth-param1' => 'value1',
				'hashes-oauth-param2' => 'value2',
			),
			$this->options
		);
	}
}
