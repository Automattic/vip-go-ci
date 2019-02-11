<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );

final class MiscTests extends TestCase {
	public function testCache1() {
		$cache_id =
			__CLASS__ .
			'_' . 
			__FUNCTION__ .
			'_mytest';

		$r = openssl_random_pseudo_bytes(
			100
		);

		vipgoci_cache(
			$cache_id,
			$r
		);

		vipgoci_cache(
			$cache_id . '_nonrelated',
			$r . $r
		);

		$r_retrieved = vipgoci_cache(
			$cache_id
		);

		return $this->assertEquals(
			$r,
			$r_retrieved
		);
	}
}
