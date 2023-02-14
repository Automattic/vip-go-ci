<?php
/**
 * Test function vipgoci_github_team_members_many_get().
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
final class GitHubTeamMembersManyTest extends TestCase {
	/**
	 * Options array.
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
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		foreach ( $this->options as $option_key => $option_value ) {
			$this->options[ $option_key ] =
				vipgoci_unittests_get_config_value(
					'git-secrets',
					$option_key,
					true
				);
		}
	}

	/**
	 * Clean up function.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_github_team_members_many_get
	 *
	 * @return void
	 */
	public function testTeamMembersMany1() :void {
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


		vipgoci_unittests_output_suppress();

		$team_members_res1_actual = vipgoci_github_team_members_many_get(
			$this->options['github-token'],
			$this->options['org-name'],
			array(
				$this->options['team-slug'],
				$this->options['team-slug'],
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$team_members_res1_actual,
			'Got no team members from vipgoci_github_team_members_many_get()'
		);

		$this->assertTrue(
			is_numeric(
				$team_members_res1_actual[0]
			)
		);

		unset( $team_members_res1_actual );
	}
}
