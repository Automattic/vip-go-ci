<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubOrgTeamsTest extends TestCase {
	var $options = array(
		'github-token'	=> null,
		'org-name'	=> null,
		'team-slug'	=> null,
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
	 * @covers ::vipgoci_github_org_teams
	 */
	public function testGitHubOrgTeamsNoFiltersNoKeys() {
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
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}


		/*
		 * Test vipgoci_github_org_teams() without any
		 * filters and without any output sorting.
		 */

		$teams_res_actual = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			null,
			null
		);

		$this->assertNotEmpty(
			$teams_res_actual,
			'Got no teams from vipgoci_github_org_teams()'
		);

		$this->assertTrue(
			isset(
				$teams_res_actual[0]->name
			)
		);

		$this->assertTrue(
			strlen(
				$teams_res_actual[0]->name
			) > 0
		);

		/*
		 * Test the caching-functionality
		 */
		$teams_res_actual_cached = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			null,
			null
		);

		unset( $teams_res_actual );
		unset( $teams_res_actual_cached );
	}

	/**
	 * @covers ::vipgoci_github_org_teams
	 */
	public function testGitHubOrgTeamsWithFilters() {
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
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}

		/*
		 * Test vipgoci_github_org_teams() with filters but
		 * without any output sorting.
		 */

		$teams_res_actual = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			array(
				'slug' => $this->options['team-slug']
			),
			null
		);


		$this->assertNotEmpty(
			$teams_res_actual,
			'Got no teams from vipgoci_github_org_teams()'
		);

		$this->assertTrue(
			isset(
				$teams_res_actual[0]->name
			)
		);

		$this->assertTrue(
			strlen(
				$teams_res_actual[0]->name
			) > 0
		);

		/*
		 * Test again, now the cached version.
		 */

		$teams_res_actual_cached = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			array(
				'slug' => $this->options['team-slug']
			),
			null
		);

		$this->assertEquals(
			$teams_res_actual,
			$teams_res_actual_cached
		);

		unset( $teams_res_actual );
		unset( $teams_res_actual_cached );
	}

	/**
	 * @covers ::vipgoci_github_org_teams
	 */
	public function testGitHubOrgTeamsWithKeyes() {
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
				'Must set up ' . __FUNCTION__ . '() test'
			);

			return;
		}


		/*
		 * Test vipgoci_github_org_teams() without filters but
		 * with output keyed.
		 */

		$teams_res_actual = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			null,
			'slug'
		);


		$this->assertNotEmpty(
			$teams_res_actual,
			'Got no teams from vipgoci_github_org_teams()'
		);

		$teams_res_actual_keys = array_keys(
			$teams_res_actual
		);

		$this->assertTrue(
			isset(
				$teams_res_actual[
					$teams_res_actual_keys[0]
				][0]->name
			)
		);

		$this->assertTrue(
			strlen(
				$teams_res_actual[
					$teams_res_actual_keys[0]
				][0]->name
			) > 0
		);

		$this->assertEquals(
			$teams_res_actual_keys[0],
			$teams_res_actual[
				$teams_res_actual_keys[0]
			][0]->slug
		);


		/*
		 * Test again, now the cached version.
		 */

		$teams_res_actual_cached = vipgoci_github_org_teams(
			$this->options['github-token'],
			$this->options['org-name'],
			null,
			'slug'
		);

		$this->assertEquals(
			$teams_res_actual,
			$teams_res_actual_cached
		);

		unset( $teams_res_actual );
		unset( $teams_res_actual_cached );
	}
}
