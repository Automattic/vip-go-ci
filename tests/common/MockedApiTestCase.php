<?php
/**
 * Shared setup for API tests with deterministic HTTP responses.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Common;

use PHPUnit\Framework\TestCase;

/**
 * Load real API clients with a test-only HTTP transport in each child process.
 */
abstract class MockedApiTestCase extends TestCase {
	/**
	 * Load dependencies and reset both transport expectations and API caches.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../cache.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/helper/HttpApiMock.php';
		require_once __DIR__ . '/helper/HttpApiFunctions.php';

		$GLOBALS['vipgoci_debug_level'] = -1;
		HttpApiMock::reset();
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
	}

	/**
	 * Require every queued response to have been consumed.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		try {
			HttpApiMock::assert_complete();
		} finally {
			HttpApiMock::reset();
			vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		}
	}
}
