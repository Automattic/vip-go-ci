<?php
/**
 * Test vipgoci_results_remove_existing_github_comments() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_results_remove_existing_github_comments' )]
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
		'pr-number-2'                           => null,
		'commit-test-results-remove-existing-1' => null,
	);

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

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
	 * Provide PRs sharing a commit, with comments present only on the second PR.
	 *
	 * @return array Test cases with expected remaining issues and error counts.
	 */
	public static function commentRemovalCases(): array {
		return array(
			'comments on another PR are preserved' => array(
				'pr-number-1',
				array(
					self::COMMENTS_DATA['comment_removable'],
					self::COMMENTS_DATA['comment_not_removable'],
				),
				2,
			),
			'same-PR comments require authenticated ownership'  => array(
				'pr-number-2',
				array( self::COMMENTS_DATA['comment_not_removable'] ),
				2,
			),
		);
	}

	/**
	 * Remove comments only when already posted on the PR being scanned.
	 * Does not process dismissed reviews.
	 *
	 * @param string $pr_option            Configuration key for the PR to scan.
	 * @param array  $expected_issues      Issues that should remain after deduplication.
	 * @param int    $expected_error_count Error count after deduplication.
	 *
	 * @return void
	 */
	#[DataProvider( 'commentRemovalCases' )]
	public function testRemovingComments( string $pr_option, array $expected_issues, int $expected_error_count ): void {
		$pr_number = (int) $this->options[ $pr_option ];

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
			$pr_number => (object) array(
				'number'     => $pr_number,
				'created_at' => '2020-01-01T00:00:01Z',
			),
		);

		$results_actual = array(
			'issues' => array(),
			'stats'  => array(),
		);

		$results_expected = $results_actual;

		$results_actual['issues'][ $pr_number ] = array(
			self::COMMENTS_DATA['comment_removable'],
			self::COMMENTS_DATA['comment_not_removable'],
		);

		$results_actual['stats'][ VIPGOCI_STATS_PHPCS ][ $pr_number ] = array(
			'error' => 2,
		);

		// Establish fixture ownership independently of the deduplication getter.
		// Read-only CI credentials can differ from the account that posted it.
		vipgoci_unittests_output_suppress();
		$identity = vipgoci_github_authenticated_user_get( $this->options['token'] );
		$comments = vipgoci_github_pr_reviews_comments_get_by_pr( $this->options, $pr_number );
		$commits = vipgoci_github_prs_commits_list(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$pr_number,
			$this->options['token']
		);
		vipgoci_unittests_output_unsuppress();
		$this->assertIsInt( $identity->id );
		$owns_fixture = false;
		foreach ( $comments as $comment ) {
			if (
				$identity->id === $comment->user->id &&
				'test.php' === $comment->path &&
				3 === $comment->position &&
				in_array( $comment->original_commit_id, $commits, true )
			) {
				$owns_fixture = true;
			}
		}
		if ( ! $owns_fixture ) {
			$expected_issues = array_values( self::COMMENTS_DATA );
		}

		$results_expected['issues'][ $pr_number ] = $expected_issues;

		$results_expected['stats'][ VIPGOCI_STATS_PHPCS ][ $pr_number ] = array(
			'error' => $expected_error_count,
		);

		vipgoci_unittests_output_suppress();

		vipgoci_results_remove_existing_github_comments(
			$this->options,
			$prs_implicated,
			$results_actual,
			false,
			array()
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$results_expected,
			$results_actual
		);
	}
}
