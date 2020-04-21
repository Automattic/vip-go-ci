<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SupportLevelLabelMetaApiDataFetchTest extends TestCase {
	var $options_meta_api = array(
		'api-base-url'		=> null,
		'api-user-id'		=> null,
		'api-access-token'	=> null,

		'repo-owner'		=> null,
		'repo-name'		=> null,

		'support-tier-name'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'repo-meta-api',
			$this->options_meta_api,
			true
		);

		$this->options = $this->options_meta_api;
	}

	protected function tearDown() {
		$this->options_meta_api = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_fetch
	 */
	public function testMetaApiDataFetch() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'api-user-id', 'api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
			$this->options['api-base-url'],
			$this->options['api-user-id'],
			$this->options['api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertTrue(
			count(
				$repo_meta_data['data']
			) > 0
		);

		$this->assertTrue(
			( ! empty(
				$repo_meta_data['data'][0]['support_tier']
			) )
		);

		$this->assertEquals(
			$this->options['support-tier-name'],
			$repo_meta_data['data'][0]['support_tier']
		);

		/*
		 * Re-test due to caching.
		 */
		$repo_meta_data_2 = vipgoci_repo_meta_api_data_fetch(
			$this->options['api-base-url'],
			$this->options['api-user-id'],
			$this->options['api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertEquals(
			$repo_meta_data,
			$repo_meta_data_2
		);
	}
}
