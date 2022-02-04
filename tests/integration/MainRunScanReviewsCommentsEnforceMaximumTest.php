<?php

declare( strict_types=1 );

namespace Vipgoci\Tests\Integration;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class MainRunScanReviewsCommentsEnforceMaximumTest extends TestCase {
	var $options_git = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
	);

	var $options_git_repo_tests = array(
		'pr-test-github-pr-results-max'	=> null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'git-repo-tests',
			$this->options_git_repo_tests
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_git_repo_tests
		);

		$this->options['token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		$this->results = array(
			'issues'	=> array(
				$this->options['pr-test-github-pr-results-max']	=> array(
					array(
						'type'		=> 'phpcs',
						'file_name'	=> 'bla-8.php',
						'file_line'	=> 9,
						'issue'		=> array(
							'message'	=> 'This comment is 36% valid code; is this commented out code?',
							'source'	=> 'Squiz.PHP.CommentedOutCode.Found',
							'severity'	=> 1,
							'fixable'	=> false,
							'type'		=> 'WARNING',
							'line'		=> 9,
							'column'	=> 1,
							'level'	=> 'WARNING'
						),
					),

					array(
						'type'		=> 'phpcs',
						'file_name'	=> 'bla-9.php',
						'file_line'	=> 10,
						'issue'		=> array(
							'message'	=> 'This comment is 100% valid code; is this commented out code?',
							'source'	=> 'Squiz.PHP.CommentedOutCode.Found',
							'severity'	=> 10,
							'fixable'	=> false,
							'type'		=> 'WARNING',
							'line'		=> 10,
							'column'	=> 1,
							'level'	=> 'WARNING'
						),
					),
				)
			),

			'stats'		=> array(
				'phpcs'		=> array(
					$this->options['pr-test-github-pr-results-max'] => array(
						'error'		=> 0,
						'warning'	=> 2,
						'info'		=> 0,
					)
				)
			),
		);

		$this->results_orig = $this->results;
	}

	protected function tearDown(): void {
		$this->options = null;
		$this->options_git = null;
		$this->options_git_repo_tests = null;
		$this->results = null;
	}

	/**
	 * @covers ::vipgoci_run_scan_reviews_comments_enforce_maximum
	 */
	public function testRunScanReviewsCommentsEnforceMaximum1() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		/*
		 * Test with no maximum comments allowed.
		 */
		$this->options['review-comments-total-max'] = 0;

		$prs_comments_maxed = vipgoci_run_scan_reviews_comments_enforce_maximum(
			$this->options,
			$this->results
		);

		$this->assertSame(
			array(
			),
			$prs_comments_maxed
		);

		$this->assertSame(
			$this->results_orig,
			$this->results
		);
	}

	/**
	 * @covers ::vipgoci_run_scan_reviews_comments_enforce_maximum
	 */
	public function testRunScanReviewsCommentsEnforceMaximum2() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		/*
		 * Test with max 1 comments allowed.
		 */
		$this->options['review-comments-total-max'] = 1;

		$prs_comments_maxed = vipgoci_run_scan_reviews_comments_enforce_maximum(
			$this->options,
			$this->results
		);

		$this->assertSame(
			array(
				$this->options['pr-test-github-pr-results-max'] => true
			),
			$prs_comments_maxed
		);

		$this->assertSame(
			array(
				'issues' => array(
					$this->options['pr-test-github-pr-results-max'] => array(
					)
				),

				'stats'		=> array(
					'phpcs'		=> array(
						$this->options['pr-test-github-pr-results-max'] => array(
							'error'		=> 0,
							'warning'	=> 0,
							'info'		=> 0,
						)
					)
				)
			),
			$this->results
		);
	}
}
