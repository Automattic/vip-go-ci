<?php
/**
 * Test vipgoci_curl_headers() function.
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
final class HttpFunctionsCurlHeadersTest extends TestCase {
	/**
	 * Setup function. Require files.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../http-functions.php';
	}

	/**
	 * Test using fairly typical HTTP headers.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_curl_headers
	 */
	public function testCurlHeaders1() :void {
		$ch = curl_init();

		/*
		 * Populate headers
		 */
		vipgoci_curl_headers(
			$ch,
			'Content-Type: text/plain'
		);

		vipgoci_curl_headers(
			$ch,
			'Date: Mon, 04 Mar 2019 16:43:35 GMT'
		);

		vipgoci_curl_headers(
			$ch,
			'Location: https://www.mytestdomain.is/'
		);

		vipgoci_curl_headers(
			$ch,
			'Status: 200 OK'
		);

		$actual_results = vipgoci_curl_headers(
			null,
			null
		);

		$this->assertSame(
			array(
				'content-type' => array( 'text/plain' ),
				'date'         => array( 'Mon, 04 Mar 2019 16:43:35 GMT' ),
				'location'     => array( 'https://www.mytestdomain.is/' ),
				'status'       => array( '200', 'OK' ),
			),
			$actual_results
		);

		curl_close( $ch );
	}

	/**
	 * Test using HTTP/2 Status compatibility header.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_curl_headers
	 */
	public function testCurlHeaders2() :void {
		$ch = curl_init();

		/*
		 * Populate headers
		 */
		vipgoci_curl_headers(
			$ch,
			'HTTP/2 205'
		);

		vipgoci_curl_headers(
			$ch,
			'Date: Mon, 04 Mar 2020 16:43:35 GMT'
		);

		vipgoci_curl_headers(
			$ch,
			'Location: https://www.mytestdomain2.is/'
		);

		$actual_results = vipgoci_curl_headers(
			null,
			null
		);

		$this->assertSame(
			array(
				'status'   => array( '205' ),
				'date'     => array( 'Mon, 04 Mar 2020 16:43:35 GMT' ),
				'location' => array( 'https://www.mytestdomain2.is/' ),
			),
			$actual_results
		);
	}

	/**
	 * Test using headers seen twice, among
	 * headers that are seen only once.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_curl_headers
	 */
	public function testCurlHeaders3() :void {
		$ch = curl_init();

		/*
		 * Populate headers
		 */
		vipgoci_curl_headers(
			$ch,
			'Date: Mon, 04 Mar 2020 16:43:35 GMT'
		);

		vipgoci_curl_headers(
			$ch,
			'X-Test-Header: value1',
		);

		vipgoci_curl_headers(
			$ch,
			'X-Test-Header: value2',
		);

		vipgoci_curl_headers(
			$ch,
			'X-Test-Header: value3',
		);

		vipgoci_curl_headers(
			$ch,
			'X-Another-Test-Header: value1',
		);

		$actual_results = vipgoci_curl_headers(
			null,
			null
		);

		$this->assertSame(
			array(
				'date'                  => array( 'Mon, 04 Mar 2020 16:43:35 GMT' ),
				'x-test-header'         => array( 'value1', 'value2', 'value3' ),
				'x-another-test-header' => array( 'value1' ),
			),
			$actual_results
		);
	}
}
