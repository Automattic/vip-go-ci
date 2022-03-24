<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubFetchUrlTest extends TestCase {
	/**
	 * @covers ::vipgoci_http_api_fetch_url
	 */
	public function testGitHubFetchUrl1() {
		$ret = vipgoci_http_api_fetch_url(
			'https://api.github.com/rate_limit',
			''
		);

		$ret = json_decode(
			$ret,
			false
		);

		$this->assertTrue(
			isset(
				$ret->rate->limit
			)
		);

		$this->assertTrue(
			isset(
				$ret->resources->core->remaining
			)
		);		
	}
}
