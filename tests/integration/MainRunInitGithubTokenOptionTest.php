<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once __DIR__ . '/IncludesForTests.php';

require_once __DIR__ . '/../unit/helper/IndicateTestId.php';

use PHPUnit\Framework\TestCase;

/**
 * Tests if GitHub token related options are processed correctly.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitGithubTokenOptionTest extends TestCase {
	/**
	 * Set up variables and indication.
	 */
	protected function setUp(): void {
		$this->options = array();

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options['token'] =
			$this->options['github-token'];

		// Indicate that this particular test is running.
		vipgoci_unittests_indicate_test_id( 'MainRunInitGithubTokenOptionTest' );
	}

	/**
	 * Clear variables and indication.
	 */
	protected function tearDown(): void {
		$this->options = null;

		// Remove the indication.
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunInitGithubTokenOptionTest' );
	}

	/**
	 * Uses valid GitHub token to test if options are correctly processed.
	 *
	 * @covers ::vipgoci_run_init_github_token_option
	 */
	public function testGitHubTokenValid() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		ob_start();

		// Initialize GitHub token options.
		vipgoci_run_init_github_token_option( $this->options );

		/*
		 * Get printed data, check if expected string was printed.
		 */
		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		$printed_data_found = strpos(
			$printed_data,
			'Got information about token-holder user from GitHub'
		);

		$this->assertNotFalse( $printed_data_found );

		$cleaned_options = vipgoci_options_sensitive_clean(
			$this->options
		);

		$this->assertSame(
			'***',
			$cleaned_options['token']
		);
	}

	/**
	 * Uses invalid GitHub token to test if options are correctly processed.
	 *
	 * @covers ::vipgoci_run_init_github_token_option
	 */
	public function testGitHubTokenInvalid() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Set token to invalid.
		$this->options['token'] = 'invalid-token-' . time();

		ob_start();

		// Initialize GitHub token options.
		vipgoci_run_init_github_token_option( $this->options );

		/*
		 * Get printed data, check if expected string was printed.
		 */
		$printed_data = ob_get_contents();

		ob_end_clean();

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		$printed_data_found = strpos(
			$printed_data,
			'Unable to get information about token-holder user from GitHub'
		);

		$this->assertNotFalse( $printed_data_found );

		$cleaned_options = vipgoci_options_sensitive_clean(
			$this->options
		);

		$this->assertSame(
			'***',
			$cleaned_options['token']
		);
	}
}
