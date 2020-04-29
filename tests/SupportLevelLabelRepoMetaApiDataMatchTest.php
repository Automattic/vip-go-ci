<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SupportLevelLabelRepoMetaApiDataMatchTest extends TestCase {
	var $options_meta_api_secrets = array(
		'repo-meta-api-base-url'	=> null,
		'repo-meta-api-user-id'		=> null,
		'repo-meta-api-access-token'	=> null,

		'repo-name'			=> null,
		'repo-owner'			=> null,

		'support-tier-name'		=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'repo-meta-api-secrets',
			$this->options_meta_api_secrets,
			true
		);

		$this->options = $this->options_meta_api_secrets;

		$this->options['data_match0'] = array(
		);

		$this->options['data_match1'] = array(
			'__invalid_field'	=> '__somethinginvalid',
		);

		$this->options['data_match2'] = array(
			'support_tier'		=> $this->options['support-tier-name'],
		);

		$this->options['branches-ignore'] = array();
	}

	protected function tearDown() {
		$this->options = null;
		$this->options_meta_api_secrets = null;
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->assertEquals(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				''
			)
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->assertEquals(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match0'
			)
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->assertEquals(
			false,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match1'
			)
		);
	}

	/**
	 * @covers ::vipgoci_repo_meta_api_data_match
	 */
	public function test_repo_meta_api_data_match4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->assertEquals(
			true,

			vipgoci_repo_meta_api_data_match(
				$this->options,
				'data_match2'
			)
		);
	}
}
