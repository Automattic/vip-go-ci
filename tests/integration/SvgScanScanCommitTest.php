<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

/**
 * Minimal testing of scanning whole commits
 * using SVG scanner. The reason is that most 
 * of the functionality provided by 
 * vipgoci_phpcs_scan_commit() -- the
 * function being tested here -- is tested in 
 * PhpcsScanScanCommitTest.php already and 
 * re-implementing these tests here will not 
 * yield much benefit.
 * 
 * Here we only test if SVG scanning does work 
 * as expected.
 */
final class SvgScanScanCommitTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	var $options_svg = array(
		'svg-scanner-path'                        => null,
		'svg-php-path'                            => null,
		'commit-test-svg-scan-single-file-test-1' => null, // Re-use commit from SvgScanScanSingleFileTest
	);

	var $options_git_repo = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'git-path'        => null,
		'github-repo-url' => null,
	);

	protected function setUp(): void {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git_repo
		);

		vipgoci_unittests_get_config_values(
			'svg-scan',
			$this->options_svg
		);

		$this->options = array_merge(
			$this->options_git_repo,
			$this->options_svg
		);

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['branches-ignore'] = array();

		$this->options['phpcs'] = true;

		$this->options['svg-checks'] = true;

		$this->options['lint-skip-folders'] = array();

		$this->options['phpcs-skip-folders'] = array();

		$this->options['skip-draft-prs'] = false;

		$this->options['phpcs-file-extensions'] = array( 'php', 'js' );

		$this->options['phpcs-skip-scanning-via-labels-allowed'] = false;

		$this->options['svg-file-extensions'] = array( 'svg' );

		$this->options['skip-large-files'] = false;

		$this->options['skip-large-files-limit'] = 15;

		$this->options['lint-modified-files-only'] = false;
	}

	protected function tearDown(): void {
		if ( false !== $this->options['local-git-repo'] ) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options_svg );
		unset( $this->options_git_repo );
		unset( $this->options );
	}

	/**
	 * Test SVG scanning of whole commit.
	 *
	 * @covers ::vipgoci_phpcs_scan_commit
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options['commit'] = $this->options['commit-test-svg-scan-single-file-test-1'];

		$issues_submit = array();
		$issues_stats = array();
		$issues_skipped = array();

		vipgoci_unittests_output_suppress();

		$prs_implicated = $this->getPRsImplicated();

		vipgoci_unittests_output_unsuppress();

		foreach( $prs_implicated as $pr_item ) {
			$issues_stats[
			$pr_item->number
			][
			'error'
			] = 0;

			$issues_skipped[ $pr_item->number ][ 'issues' ][ 'max-lines' ] = array();
			$issues_skipped[ $pr_item->number ][ 'issues' ][ 'total' ] = 0;
		}

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

		vipgoci_phpcs_scan_commit(
			$this->options,
			$issues_submit,
			$issues_stats,
			$issues_skipped
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				5 => array(
					array(
						'type'          => 'phpcs',
						'file_name'     => 'svg-file-with-issues-1.svg',
						'file_line'     => 8,
						'issue'	=> array(
							'message'  => "Suspicious attribute 'someotherfield2'",
							'line'     => 8,
							'severity' => 5,
							'source'   => 'VipgociInternal.SVG.DisallowedTags',
							'level'    => 'ERROR',
							'fixable'  => false,
							'column'   => 0,
						)
					),

					array(
						'type'          => 'phpcs',
						'file_name'     => 'svg-file-with-issues-1.svg',
						'file_line'     => 5,
						'issue'         => array(
							'message'  => "Suspicious attribute 'myotherfield'",
							'line'     => 5,
							'severity' => 5,
							'source'   => 'VipgociInternal.SVG.DisallowedTags',
							'level'    => 'ERROR',
							'fixable'  => false,
							'column'   => 0,
						)
					)
				)
			),
  
			$issues_submit
		);

		$this->assertSame(
			array(
				5 => array(
					'error' => 2,
				)
			),
			$issues_stats
		);
	}

	/**
	 * @return array|bool|mixed|null
	 */
	public function getPRsImplicated() {
		vipgoci_unittests_output_suppress();

		$prs_implicated = vipgoci_github_prs_implicated(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['commit'],
			$this->options['github-token'],
			$this->options['branches-ignore']
		);

		vipgoci_unittests_output_unsuppress();

		return $prs_implicated;
	}
}
