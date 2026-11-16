<?php
/**
 * Test vipgoci_auto_approval_scan_commit() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_auto_approval_scan_commit' )]
final class AutoApprovalScanCommitTest extends TestCase {
	private ?LivePrState $pr_state = null;
	private string $unsafe_reason = 'Test not safe to run due to earlier warnings';
	private int $output_level = 0;
	/**
	 * Variable that indicates if it is safe to run
	 * tests.
	 *
	 * @var $safe_to_run
	 */
	private mixed $safe_to_run = null;

	/**
	 * Variable for auto-approval options.
	 *
	 * @var $options_auto_approvals
	 */
	private array $options_auto_approvals = array();

	/**
	 * Variable for git options.
	 *
	 * @var $options_git
	 */
	private array $options_git = array();

	/**
	 * Variable for options.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Setup function. Require files, set up variables, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$this->output_level = ob_get_level();
		require_once __DIR__ . '/IncludesForTests.php';
		require_once __DIR__ . '/helper/LivePrState.php';

		$this->options_git = array(
			'git-path'        => null,
			'github-repo-url' => null,
			'repo-owner'      => null,
			'repo-name'       => null,
		);

		$this->options_auto_approvals = array(
			'pr-test-ap-auto-approval-1'     => null,
			'commit-test-ap-auto-approval-1' => null,
		);

		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_auto_approvals
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['commit'] =
			$this->options['commit-test-ap-auto-approval-1'];

		$this->options['dry-run'] = false;

		$this->options['branches-ignore'] = array();

		$this->options['autoapprove'] = true;

		$this->options['autoapprove-label'] =
			'Autoapproved Pull-Request';

		// Not used in this test, but needs to be defined.
		$this->options['autoapprove-filetypes'] =
			array(
				'css',
				'txt',
				'json',
				'md',
			);

		// Same, not used, but needs to be defined.
		$this->options['autoapprove-php-nonfunctional-changes'] = false;

		// Settings for WPScan, disable by default, dry-mode disabled.
		$this->options['wpscan-api']          = false;
		$this->options['wpscan-api-dry-mode'] = false;

		$this->options['skip-draft-prs'] = false;

		$this->options['local-git-repo'] = false;

		$this->options['pr-test-ap-auto-approval-1'] =
			(int) $this->options['pr-test-ap-auto-approval-1'];

		if ( ! vipgoci_unittests_get_skip_github_write_tests() ) {
			$this->check_pr_and_capture_state();
		}
	}

	/**
	 * Tear down function. Unset variables, remove
	 * local git repo if set up.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		try {
			$this->close_test_output_buffers();
			$this->pr_state?->restore();
		} finally {
			$this->close_test_output_buffers();
			if ( false !== ( $this->options['local-git-repo'] ?? false ) ) {
				vipgoci_unittests_remove_git_repo( $this->options['local-git-repo'] );
			}
			unset( $this->options_git, $this->options_auto_approvals, $this->options );
		}
	}

	private function close_test_output_buffers(): void {
		while ( ob_get_level() > $this->output_level ) {
			ob_end_clean();
		}
	}

	/**
	 * Validate that the commit belongs only to the configured PR and
	 * capture the state before the subject can write to it.
	 *
	 * @return void
	 */
	private function check_pr_and_capture_state() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->safe_to_run = 1 === count( $prs_implicated );
		foreach ( $prs_implicated as $pr_item ) {
			if (
				(int) $pr_item->number
				!==
				(int) $this->options['pr-test-ap-auto-approval-1']
			) {
				printf(
					'Warning: Got unexpected pull request; pr-number=%d, expected-pr-number=%d, pr_item=%s',
					(int) $pr_item->number,
					(int) $this->options['pr-test-ap-auto-approval-1'],
					print_r( $pr_item, true )
				);

				$this->safe_to_run = false;
			}
		}
		if ( false === $this->safe_to_run ) {
			return;
		}

		$marker = 'vipgoci-test-' . bin2hex( random_bytes( 8 ) );
		$this->options['autoapprove-filetypes'][] = $marker;
		$this->pr_state = new LivePrState(
			$this->options['repo-owner'], $this->options['repo-name'],
			$this->options['token'], $this->options['pr-test-ap-auto-approval-1'],
			array( $this->options['autoapprove-label'] ), $marker, $this->options['commit']
		);
		if ( $this->pr_state->has_own_approval() ) {
			$this->safe_to_run = false;
			$this->unsafe_reason = 'Configured test PR has a preexisting approval by this account; production nonapproval would dismiss it';
		}
	}

	/**
	 * Get labels for pull request used in these tests.
	 *
	 * @return mixed
	 */
	private function pr_get_labels() :mixed {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'issues/' .
			rawurlencode( (string) $this->options['pr-test-ap-auto-approval-1'] ) . '/' .
			'labels';

		$data = vipgoci_http_api_fetch_url(
			$github_url,
			$this->options['token']
		);

		$data = json_decode( $data );

		foreach ( $data as $data_item ) {
			if (
				$data_item->name ===
				$this->options['autoapprove-label']
			) {
				return $data_item;
			}
		}

		return false;
	}

	/**
	 * Test auto-approvals for PR that should
	 * auto-appove.
	 */
	public function testAutoApproval1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				'file-1.php'  => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
				'file-2.css'  => 'autoapprove-filetypes',
				'file-3.txt'  => 'autoapprove-filetypes',
				'file-4.json' => 'autoapprove-filetypes',
				'README.md'   => 'autoapprove-filetypes',
			),
		);

		$results = array();

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Ensure the correct pull request was retrieved.
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // Skip cache.
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				1,
				count( $pr_item_reviews )
			);

			foreach ( $pr_item_reviews as $pr_item_review ) {
				$this->assertSame(
					'APPROVED',
					$pr_item_review->state
				);
			}
		}

		$labels = $this->pr_get_labels();

		$this->assertSame(
			$this->options['autoapprove-label'],
			$labels->name
		);
	}

	/**
	 * Test auto-approvals for PR that should
	 * not auto-appove.
	 */
	public function testAutoApproval2() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				// Note: file-1.php is NOT approved.
				'file-2.css'  => 'autoapprove-filetypes',
				'file-3.txt'  => 'autoapprove-filetypes',
				'file-4.json' => 'autoapprove-filetypes',
				'README.md'   => 'autoapprove-filetypes',
			),
		);

		$results = array();

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(),
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Ensure the correct pull request was retrieved.
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // Skip cache.
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				0,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			false,
			$label
		);
	}

	/**
	 * Test auto-approvals for PR that should
	 * not auto-appove, but should leave a comment
	 * about one PHP file that is approved.
	 */
	public function testAutoApproval3() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}
		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				// Note: file-1.php is approved, but
				// some of the other files are not.
				'file-1.php' => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
				'file-2.css' => 'autoapprove-filetypes',
				'file-3.txt' => 'autoapprove-filetypes',
				// file-4.json is not approved.
				'README.md'  => 'autoapprove-filetypes',
			),
		);

		$results = array(
			'stats' => array(),
		);

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Ensure the correct pull request was retrieved.
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // Skip cache.
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				0,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			false,
			$label
		);

		$this->assertSame(
			array(
				'stats' => array(),
			),
			$results
		);
	}

	/**
	 * In vipgoci_autoapproval_do_approve() we make
	 * sure we do not re-approve already approved
	 * Pull-Requests. Make sure this really works.
	 */
	public function testAutoApproval4() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		/*
		 * Make sure no reviews indicate approval currently.
		 */
		vipgoci_unittests_output_suppress();

		$pr_item_reviews = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-ap-auto-approval-1'],
			$this->options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'APPROVED' ),
			),
			true // Skip cache.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			0,
			count( $pr_item_reviews )
		);

		/*
		 * Auto-approve the PR twice.
		 */

		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				// All files in the PR are approvable.
				'file-1.php'  => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
				'file-2.css'  => 'autoapprove-filetypes',
				'file-3.txt'  => 'autoapprove-filetypes',
				'file-4.json' => 'autoapprove-filetypes',
			),
		);

		$results = array(
			'stats' => array(),
		);

		vipgoci_unittests_output_suppress();

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		$pr_item_reviews = vipgoci_github_pr_reviews_get(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['pr-test-ap-auto-approval-1'],
			$this->options['token'],
			array(
				'login' => 'myself',
				'state' => array( 'APPROVED' ),
			),
			true // Skip cache.
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $pr_item_reviews )
		);
	}

	/**
	 * Test auto-approvals for pull request that should
	 * be auto-appoved as WPScan is enabled and
	 * no WPScan errors are present.
	 */
	public function testAutoApprovalWithoutWpscanIssues() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}
		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		// Enable WPScan API for this test.
		$this->options['wpscan-api'] = true;

		// All files are approved.
		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				'file-1.php'  => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
				'file-2.css'  => 'autoapprove-filetypes',
				'file-3.txt'  => 'autoapprove-filetypes',
				'file-4.json' => 'autoapprove-filetypes',
				'README.md'   => 'autoapprove-filetypes',
			),
		);

		$results_before = array(
			'stats' => array(
				VIPGOCI_STATS_WPSCAN_API => array(
					$this->options['pr-test-ap-auto-approval-1'] => array(
						VIPGOCI_ISSUE_TYPE_ERROR   => 0,
						VIPGOCI_ISSUE_TYPE_WARNING => 0,
					),
				),
			),
		);

		vipgoci_unittests_output_suppress();

		$results = $results_before;

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$results_before,
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Ensure the correct pull request was retrieved.
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // Skip cache.
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				1,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			$this->options['autoapprove-label'],
			$label->name
		);
	}

	/**
	 * Test auto-approvals for pull request that should
	 * not be auto-appoved due to WPScan errors present.
	 */
	public function testAutoApprovalWithWpscanIssues() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( vipgoci_unittests_skip_github_write_tests( $this ) ) {
			return;
		}

		if ( false === $this->safe_to_run ) {
			$this->markTestSkipped(
				$this->unsafe_reason
			);
		}

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

		// Enable WPScan API for this test.
		$this->options['wpscan-api'] = true;

		// All files are approved.
		$auto_approved_files_arr = array(
			(int) $this->options['pr-test-ap-auto-approval-1'] => array(
				'file-1.php'  => 'autoapprove-approved-php-file', // Not a value used generally, only for testing.
				'file-2.css'  => 'autoapprove-filetypes',
				'file-3.txt'  => 'autoapprove-filetypes',
				'file-4.json' => 'autoapprove-filetypes',
				'README.md'   => 'autoapprove-filetypes',
			),
		);

		$results_before = array(
			'stats' => array(
				VIPGOCI_STATS_WPSCAN_API => array(
					$this->options['pr-test-ap-auto-approval-1'] => array(
						VIPGOCI_ISSUE_TYPE_ERROR   => 1,
						VIPGOCI_ISSUE_TYPE_WARNING => 0,
					),
				),
			),
		);

		vipgoci_unittests_output_suppress();

		$results = $results_before;

		vipgoci_auto_approval_scan_commit(
			$this->options,
			$auto_approved_files_arr,
			$results
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$results_before,
			$results
		);

		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			1,
			count( $prs_implicated )
		);

		foreach ( $prs_implicated as $pr_item ) {
			// Ensure the correct pull request was retrieved.
			$this->assertSame(
				$this->options['pr-test-ap-auto-approval-1'],
				$pr_item->number
			);

			vipgoci_unittests_output_suppress();

			$pr_item_reviews = vipgoci_github_pr_reviews_get(
				$this->options['repo-owner'],
				$this->options['repo-name'],
				(int) $pr_item->number,
				$this->options['token'],
				array(
					'login' => 'myself',
					'state' => array( 'APPROVED' ),
				),
				true // Skip cache.
			);

			vipgoci_unittests_output_unsuppress();

			$this->assertSame(
				0,
				count( $pr_item_reviews )
			);
		}

		$label = $this->pr_get_labels();

		$this->assertSame(
			false,
			$label
		);
	}
}
