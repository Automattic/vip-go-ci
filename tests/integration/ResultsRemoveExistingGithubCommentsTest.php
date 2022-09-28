<?php
/**
 * Test vipgoci_results_remove_existing_github_comments() function.
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
final class ResultsRemoveExistingGithubCommentsTest extends TestCase {
	/**
	 * Git options variable.
	 *
	 * @var $options_git_repo
	 */
	private array $options_git_repo = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'git-path'        => null,
		'github-repo-url' => null,
	);

	/**
	 * Results options variable.
	 *
	 * @var $options_results
	 */
	private array $options_results = array(
		'pr-number-1'                           => null,
		'commit-test-results-remove-existing-1' => null,
	);

	/**
	 * Comments for tests.
	 *
	 * @var $COMMENTS_DATA
	 */
	private const COMMENTS_DATA = array(
		'comment_removable'     => array(
			'type'      => 'phpcs',
			'file_name' => 'test.php',
			'file_line' => 3,
			'issue'     => array(
				'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
				'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
				'severity' => 5,
				'fixable'  => false,
				'line'     => 3,
				'column'   => 20,
				'level'    => 'ERROR',
			),
		),
		'comment_not_removable' => array(
			'type'      => 'phpcs',
			'file_name' => 'test.php',
			'file_line' => 1000,
			'issue'     => array(
				'message'  => "All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found 'time'.",
				'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
				'severity' => 5,
				'fixable'  => false,
				'line'     => 1000,
				'column'   => 20,
				'level'    => 'ERROR',
			),
		),
	);

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git_repo
		);

		vipgoci_unittests_get_config_values(
			'results',
			$this->options_results
		);

		$this->options = array_merge(
			$this->options_git_repo,
			$this->options_results,
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['skip-draft-prs'] = false;
	}

	/**
	 * Teardown function.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options_git_repo );
		unset( $this->options_results );
		unset( $this->options );
	}

	/**
	 * Test if comments are removed from results when they have
	 * already been posted on GitHub. Does not process dismissed
	 * reviews.
	 *
	 * @covers ::vipgoci_results_remove_existing_github_comments
	 *
	 * @return void
	 */
	public function testRemovingComments(): void {
		$this->options['commit'] =
			$this->options['commit-test-results-remove-existing-1'];

		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_unittests_output_unsuppress();

		$prs_implicated = array(
			$this->options['pr-number-1'] => (object) array(
				'number'     => (int) $this->options['pr-number-1'],
				'created_at' => '2020-01-01T00:00:01Z',
			),
		);

		$results_actual = array(
			'issues' => array(),
			'stats'  => array(),
		);

		$results_expected = $results_actual;

		$results_actual['issues'][ $this->options['pr-number-1'] ] = array(
			self::COMMENTS_DATA['comment_removable'],
			self::COMMENTS_DATA['comment_not_removable'],
		);

		$results_actual['stats'][ VIPGOCI_STATS_PHPCS ][ $this->options['pr-number-1'] ] = array(
			'error' => 2,
		);

		$results_expected['issues'][ $this->options['pr-number-1'] ] = array(
			self::COMMENTS_DATA['comment_not_removable'],
		);

		$results_expected['stats'][ VIPGOCI_STATS_PHPCS ][ $this->options['pr-number-1'] ] = array(
			'error' => 1,
		);

		vipgoci_results_remove_existing_github_comments(
			$this->options,
			$prs_implicated,
			$results_actual,
			false,
			array()
		);

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}
}
