<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class MainRunInitOptionsRepoOptionsTest extends TestCase {
	var $options_git = array(
		'git-path'        => null,
		'github-repo-url' => null,
		'repo-name'       => null,
		'repo-owner'      => null,
	);

	var $options_options = array(
		'commit-test-options-read-repo-file-with-file-2' => null,
	);

	protected function setUp() :void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);
		
		vipgoci_unittests_get_config_values(
			'options',
			$this->options_options
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_options
		);
	
		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['commit'] =
			$this->options['commit-test-options-read-repo-file-with-file-2'];
	}

	protected function tearDown() :void {
		unset( $this->options );
		unset( $this->options_options );
		unset( $this->options_git );
	}

	/**
	 * @covers ::vipgoci_run_init_options_repo_options
	 */
	public function testRunInitOptionsRepoOptionsDisabled() :void {
		$this->options['local-git-repo'] = vipgoci_unittests_setup_git_repo(
			$this->options
		);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		// Options parameters processed by vipgoci_run_init_options_repo_options()
		$this->options['repo-options'] = 'false';
		$this->options['repo-options-allowed'] = 'post-generic-pr-support-comments,skip-draft-prs';

		// Processed by the function, neither should be changed
		$this->options['post-generic-pr-support-comments'] = false;
		$this->options['skip-draft-prs'] = false;

		vipgoci_unittests_output_suppress();

		vipgoci_run_init_options_repo_options( $this->options );

		vipgoci_unittests_output_unsuppress();

		$this->assertFalse(
			$this->options['post-generic-pr-support-comments'],
			'post-generic-pr-support-comments is incorrectly set in $options'
		);

		$this->assertFalse(
			$this->options['skip-draft-prs'],
			'skip-draft-prs is incorrectly set in $options'
		);

		$this->assertSame(
			array( 'post-generic-pr-support-comments', 'skip-draft-prs' ),
			$this->options['repo-options-allowed']
		);

		vipgoci_unittests_remove_git_repo( $this->options['local-git-repo'] );
	}

	/**
	 * @covers ::vipgoci_run_init_options_repo_options
	 */
	public function testRunInitOptionsRepoOptionsEnabledAndAllowed() :void {
		$this->options['commit'] = $this->options['commit-test-options-read-repo-file-with-file-2'];

		$this->options['local-git-repo'] = vipgoci_unittests_setup_git_repo(
			$this->options
		);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		// Options parameters processed by vipgoci_run_init_options_repo_options()
		$this->options['repo-options'] = 'true';
		$this->options['repo-options-allowed'] = 'post-generic-pr-support-comments,skip-draft-prs';

		// Processed by the function, one should be changed, other not
		$this->options['post-generic-pr-support-comments'] = false;
		$this->options['skip-draft-prs'] = false;

		vipgoci_run_init_options_repo_options( $this->options );

		$this->assertTrue(
			$this->options['post-generic-pr-support-comments'],
			'post-generic-pr-support-comments is incorrectly set in $options'
		);

		$this->assertFalse(
			$this->options['skip-draft-prs'],
			'skip-draft-prs is incorrectly set in $options'
		);

		$this->assertSame(
			array( 'post-generic-pr-support-comments', 'skip-draft-prs' ),
			$this->options['repo-options-allowed']
		);

		vipgoci_unittests_remove_git_repo( $this->options['local-git-repo'] );
	}

	/**
	 * @covers ::vipgoci_run_init_options_repo_options
	 */
	public function testRunInitOptionsRepoOptionsEnabledAndNotAllowed() :void {
		$this->options['commit'] = $this->options['commit-test-options-read-repo-file-with-file-2'];

		$this->options['local-git-repo'] = vipgoci_unittests_setup_git_repo(
			$this->options
		);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		// Options parameters processed by vipgoci_run_init_options_repo_options()
		$this->options['repo-options'] = 'true';
		$this->options['repo-options-allowed'] = 'skip-draft-prs';

		// Processed by the function, neither should be changed
		$this->options['post-generic-pr-support-comments'] = false;
		$this->options['skip-draft-prs'] = true;

		vipgoci_run_init_options_repo_options( $this->options );

		$this->assertFalse(
			$this->options['post-generic-pr-support-comments'],
			'post-generic-pr-support-comments is incorrectly set in $options'
		);

		$this->assertTrue(
			$this->options['skip-draft-prs'],
			'skip-draft-prs is incorrectly set in $options'
		);

		$this->assertSame(
			array( 'skip-draft-prs' ),
			$this->options['repo-options-allowed']
		);

		vipgoci_unittests_remove_git_repo( $this->options['local-git-repo'] );
	}
}
