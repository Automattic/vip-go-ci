<?php
/**
 * Test vipgoci_option_teams_handle().
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
final class OptionsTeamsTest extends TestCase {
	/**
	 * Options variable.
	 *
	 * @var $options
	 */
	private array $options = array(
		'github-token' => null,
		'team-slug'    => null,
		'org-name'     => null,
	);

	/**
	 * Setup function.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

		foreach ( $this->options as $option_key => $option_value ) {
			$this->options[ $option_key ] =
				vipgoci_unittests_get_config_value(
					'git-secrets',
					$option_key,
					true
				);
		}

		$this->options['repo-owner'] =
			$this->options['org-name'];

		$this->options['token'] =
			$this->options['github-token'];
	}

	/**
	 * Cleanup function.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_teams_handle
	 *
	 * @return void
	 */
	public function testVipgociOptionTeams1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}

		$this->options['my-team-option'] = array(
			$this->options['team-slug'],
		);

		vipgoci_unittests_output_suppress();

		vipgoci_option_teams_handle(
			$this->options,
			'my-team-option'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$this->options['my-team-option'],
			'Got empty result from vipgoci_option_teams_handle()'
		);

		$this->assertTrue(
			count(
				$this->options['my-team-option']
			) > 0,
			'No teams were found, at least one should be found'
		);
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_option_teams_handle
	 *
	 * @return void
	 */
	public function testVipgociOptionTeams2() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}

		$this->options['my-team-option'] = array(
			'IsInvalidteamId5000000XYZ',
		);

		vipgoci_unittests_output_suppress();

		vipgoci_option_teams_handle(
			$this->options,
			'my-team-option'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEmpty(
			$this->options['my-team-option'],
			'Got non-empty result from vipgoci_option_teams_handle()'
		);
	}
}
