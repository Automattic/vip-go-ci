<?php
/**
 * Test vipgoci_repo_meta_api_data_fetch() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class RepoMetaApiRepoMetaApiDataFetchTest extends TestCase {
	/**
	 * Options for repo meta API.
	 *
	 * @var $options_meta_api_secrets
	 */
	private array $options_meta_api_secrets = array(
		'repo-meta-api-base-url'     => null,
		'repo-meta-api-user-id'      => null,
		'repo-meta-api-access-token' => null,
		'repo-owner'                 => null,
		'repo-name'                  => null,
		'support-level'              => null,
		'support-level-field-name'   => null,
	);

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'repo-meta-api-secrets',
			$this->options_meta_api_secrets,
			true
		);

		$this->options = $this->options_meta_api_secrets;

		// This can be an empty string, set to empty if null.
		if ( null === $this->options['repo-meta-api-user-id'] ) {
			$this->options['repo-meta-api-user-id'] = '';
		}

		// This can be an empty string, set to empty if null.
		if ( null === $this->options['repo-meta-api-access-token'] ) {
			$this->options['repo-meta-api-access-token'] = '';
		}
	}

	/**
	 * Tear down function. Unset variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $this->options_meta_api_secrets );
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_repo_meta_api_data_fetch
	 */
	public function testMetaApiDataFetch() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'repo-meta-api-user-id', 'repo-meta-api-access-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$repo_meta_data = vipgoci_repo_meta_api_data_fetch(
			$this->options['repo-meta-api-base-url'],
			$this->options['repo-meta-api-user-id'],
			$this->options['repo-meta-api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertTrue(
			count(
				$repo_meta_data['data']
			) > 0
		);

		$this->assertNotEmpty(
			$repo_meta_data['data'][0][ $this->options['support-level-field-name'] ]
		);

		$this->assertSame(
			$this->options['support-level'],
			$repo_meta_data['data'][0][ $this->options['support-level-field-name'] ]
		);

		/*
		 * Re-test due to caching.
		 */
		$repo_meta_data_2 = vipgoci_repo_meta_api_data_fetch(
			$this->options['repo-meta-api-base-url'],
			$this->options['repo-meta-api-user-id'],
			$this->options['repo-meta-api-access-token'],
			$this->options['repo-owner'],
			$this->options['repo-name']
		);

		$this->assertSame(
			$repo_meta_data,
			$repo_meta_data_2
		);
	}
}
