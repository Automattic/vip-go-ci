<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubRepoCollaboratorsTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_git = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		$this->options = $this->options_git;

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);
	}

	protected function tearDown(): void {
		unset( $this->options );
		unset( $this->options_git );
	}

	/**
	 * @covers ::vipgoci_github_repo_collaborators_get
	 */
	public function testRepocollaboratorsAll() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$repo_collaborators_all = vipgoci_github_repo_collaborators_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			'all',
			array(
				'admin' => 1,
				'push' => 1,
				'pull' => 1,
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertTrue(
			count( $repo_collaborators_all ) > 5
		);

		$this->assertTrue(
			( isset( $repo_collaborators_all[0]->login ) ) &&
			( strlen( $repo_collaborators_all[0]->login ) > 0 )
		);

		$this->assertTrue(
			( isset( $repo_collaborators_all[0]->id ) ) &&
			( $repo_collaborators_all[0]->id > 0 )
		);
	}

	/**
	 * @covers ::vipgoci_github_repo_collaborators_get
	 */
	public function testRepocollaboratorsDirect() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$repo_collaborators_all = vipgoci_github_repo_collaborators_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			'direct',
			array(
				'admin' => 1,
				'push' => 1,
				'pull' => 1,
			)
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertTrue(
			count( $repo_collaborators_all ) === 0
		);
	}
}

