<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubTeamMembersTest extends TestCase {
	var $options = array(
		'github-token'	=> null,
		'team-id' => null,
	);

	public function setUp() {
		foreach( $this->options as $option_key => $option_value ) {
			$this->options[ $option_key ] =
				vipgoci_unittests_get_config_value(
					'git-secrets',
					$option_key,
					true
				);
		}
	}

	public function tearDown() {
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_team_members
	 */
	public function testTeamMembers_ids_only_false() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up testTeamMembers_ids_only_false() test'
			);

			return;
		}


		/*
		 * Test with ids_only = false
		 */

		$teams_res1_actual = vipgoci_github_team_members(
			$this->options['github-token'],
			$this->options['team-id'],
			false
		);

		$this->assertNotEmpty(
			$teams_res1_actual,
			'Got no team members from vipgoci_github_team_members()'
		);

		$this->assertTrue(
			isset(
				$teams_res1_actual[0]->login
			)
		);

		$this->assertTrue(
			strlen(
				$teams_res1_actual[0]->login
			) > 0
		);


		/*
		 * Test again to make sure the cache behaves correctly.
		 */
		$teams_res1_actual_cached = vipgoci_github_team_members(
			$this->options['github-token'],
			$this->options['team-id'],
			false
		);

		$this->assertEquals(
			$teams_res1_actual,
			$teams_res1_actual_cached
		);

		unset( $teams_res1_actual );
		unset( $teams_res1_actual_cached );
	}

	/**
	 * @covers ::vipgoci_github_team_members
	 */
	public function testTeamMembers_ids_only_true() {	
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->options ) ) {
			$this->markTestSkipped(
				'Must set up testTeamMembers_ids_only_true() test'
			);

			return;
		}

		/*
		 * Second test, with $ids_only = true
		 */

		$teams_res2_actual = vipgoci_github_team_members(
			$this->options['github-token'],
			$this->options['team-id'],
			true
		);

		$this->assertNotEmpty(
			$teams_res2_actual,
			'Got empty results when calling vipgoci_github_team_members()'
		);

		$this->assertTrue(
			isset(
				$teams_res2_actual[0]
			)
		);

		$this->assertTrue(
			is_numeric(
				$teams_res2_actual[0]
			)
		);

		// Again, for caching.
		$teams_res2_actual_cached = vipgoci_github_team_members(
			$this->options['github-token'],
			$this->options['team-id'],
			true
		);

		$this->assertEquals(
			$teams_res2_actual,
			$teams_res2_actual_cached
		);

		unset( $teams_res2_actual );
		unset( $teams_res2_actual_cached );
	}
}
