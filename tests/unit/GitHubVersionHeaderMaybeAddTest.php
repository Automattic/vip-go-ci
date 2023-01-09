<?php
/**
 * Test vipgoci_github_api_version_header_maybe_add() function.
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
final class GitHubVersionHeaderMaybeAddTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../github-api.php';
	}

	/**
	 * Test condition when header should not be added to array.
	 *
	 * @covers ::vipgoci_github_api_version_header_maybe_add
	 *
	 * @return void
	 */
	public function testVersionHeaderNoneAdded(): void {
		$http_headers_arr = array();

		vipgoci_github_api_version_header_maybe_add(
			'http://127.0.0.1/user',
			$http_headers_arr
		);

		$this->assertSame(
			array(),
			$http_headers_arr
		);
	}

	/**
	 * Test condition when header should be added to array.
	 *
	 * @covers ::vipgoci_github_api_version_header_maybe_add
	 *
	 * @return void
	 */
	public function testVersionHeaderAdded(): void {
		$http_headers_arr = array();

		vipgoci_github_api_version_header_maybe_add(
			VIPGOCI_GITHUB_BASE_URL . '/user',
			$http_headers_arr
		);

		$this->assertSame(
			array(
				'X-GitHub-Api-Version: ' . VIPGOCI_GITHUB_API_VERSION,
			),
			$http_headers_arr
		);
	}
}
