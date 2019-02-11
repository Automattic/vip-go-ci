<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );

final class MiscCacheTest extends TestCase {
	/**
	 * @covers vipgoci_cache
	 */
	public function testCache1() {
		$cache_id1 =
			__CLASS__ .
			'_' . 
			__FUNCTION__ .
			'_mytest1';

		$cache_id2 =
			__CLASS__ .
			'_' . 
			__FUNCTION__ .
			'_mytest2';

		$r1 = openssl_random_pseudo_bytes(
			100
		);

		$r2 = $r1 . $r1;

		vipgoci_cache(
			$cache_id1,
			$r1
		);

		vipgoci_cache(
			$cache_id2,
			$r2
		);

		$r1_retrieved = vipgoci_cache(
			$cache_id1
		);

		$r2_retrieved = vipgoci_cache(
			$cache_id2
		);

		$this->assertEquals(
			$r1,
			$r1_retrieved
		);

		$this->assertEquals(
			$r2,
			$r2_retrieved
		);


		$this->assertNotEquals(
			$r1,
			$r2
		);

		$this->assertNotEquals(
			$r1_retrieved,
			$r2_retrieved
		);
	}
}
