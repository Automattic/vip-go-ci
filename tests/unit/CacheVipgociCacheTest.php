<?php
/**
 * Test vipgoci_cache().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_cache' )]
final class CacheVipgociCacheTest extends TestCase {
	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../cache.php';
	}

	/**
	 * Test caching random data and test if is correctly retrieved.
	 *
	 * @return void
	 */
	public function testCache1() :void {
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

		$this->assertSame(
			$r1,
			$r1_retrieved
		);

		$this->assertSame(
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

	/**
	 * Test clearing of cache.
	 *
	 * @return void
	 */
	public function testCache2() :void {
		$cache_id =
			__CLASS__ .
			'_' .
			__FUNCTION__;

		/*
		 * Clear cache.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		/*
		 * Cache something,
		 * be sure it cached properly.
		 */
		vipgoci_cache(
			$cache_id,
			'mytext'
		);

		$cached_data = vipgoci_cache(
			$cache_id
		);

		$this->assertSame(
			'mytext',
			$cached_data
		);

		/*
		 * Clear the cache,
		 * make sure it cleared.
		 */
		vipgoci_cache(
			VIPGOCI_CACHE_CLEAR
		);

		$cached_data = vipgoci_cache(
			$cache_id
		);

		$this->assertSame(
			false,
			$cached_data
		);
	}

	/** Invalid UTF-8 path bytes must not alias other files, functions or commits. */
	public function testBinaryKeysRemainDistinctAndPreserveCacheContracts(): void {
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		$keys = array(
			array( 'validation', "file-\xff.php", 'commit-a' ),
			array( 'validation', "file-\xfe.php", 'commit-a' ),
			array( 'validation', 'file-\377.php', 'commit-a' ),
			array( 'validation', "file-\xff.php", 'commit-b' ),
			array( 'patch', "file-\xff.php", 'commit-a' ),
			array( 'nested', array( "file-\xff.php" => array( "value-\xfe" ) ) ),
			array( 'typed', 1 ),
			array( 'typed', '1' ),
		);
		foreach ( $keys as $index => $key ) {
			$this->assertSame( $index, vipgoci_cache( $key, $index ) );
		}
		foreach ( $keys as $index => $key ) {
			$this->assertSame( $index, vipgoci_cache( $key ) );
		}
		$value = (object) array( 'state' => 'original' );
		$returned = vipgoci_cache( array( "object-\xff" ), $value );
		$value->state = 'caller-mutated';
		$this->assertSame( 'original', $returned->state );
		$cached = vipgoci_cache( array( "object-\xff" ) );
		$this->assertSame( 'original', $cached->state );
		$cached->state = 'read-mutated';
		$this->assertSame( 'original', vipgoci_cache( array( "object-\xff" ) )->state );
		vipgoci_cache( VIPGOCI_CACHE_CLEAR );
		foreach ( $keys as $key ) {
			$this->assertFalse( vipgoci_cache( $key ) );
		}
	}

}
