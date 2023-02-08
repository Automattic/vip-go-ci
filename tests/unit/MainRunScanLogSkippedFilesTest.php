<?php
/**
 * Test function vipgoci_run_scan_log_skipped_files().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Checks if it is logged when a file is skipped.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunScanLogSkippedFilesTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Results array.
	 *
	 * @var $results
	 */
	private array $results = array();

	/**
	 * PRs implicated array.
	 *
	 * @var $prs_implicated
	 */
	private array $prs_implicated = array();

	/**
	 * Require file and define variables.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../defines.php';

		require_once __DIR__ . '/helper/IrcApiAlertQueue.php';

		$this->options = array(
			'repo-owner' => 'test-repo',
			'repo-name'  => 'test-name',
		);

		$this->results = array(
			VIPGOCI_SKIPPED_FILES => array(
				40 => array(
					'issues' => array(),
				),

				50 => array(
					'issues' => array(),
				),
			),
		);

		$pr_item_40         = new \stdClass();
		$pr_item_40->number = 40;

		$pr_item_50         = new \stdClass();
		$pr_item_50->number = 50;

		$this->prs_implicated = array(
			40 => $pr_item_40,
			50 => $pr_item_50,
		);
	}

	/**
	 * Clean up variables.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		unset( $this->options );
		unset( $this->results );
		unset( $this->prs_implicated );
	}

	/**
	 * Check if it is logged when a file is skipped.
	 *
	 * @covers ::vipgoci_run_scan_log_skipped_files
	 *
	 * @return void
	 */
	public function testRunScanLogSkippedFilesNoFound() :void {
		ob_start();

		vipgoci_run_scan_log_skipped_files(
			$this->options,
			$this->results,
			$this->prs_implicated
		);

		$printed_output = ob_get_clean();

		$this->assertFalse(
			strpos(
				$printed_output,
				'Too large file(s) was/were detected during analysis:'
			),
			'Message was logged about too large files'
		);
	}

	/**
	 * Check if it is not logged when a file is not skipped.
	 *
	 * @covers ::vipgoci_run_scan_log_skipped_files
	 *
	 * @return void
	 */
	public function testRunScanLogSkippedFilesFound() :void {
		// Add issues found.
		$this->results[ VIPGOCI_SKIPPED_FILES ][40]['issues'][ VIPGOCI_VALIDATION_MAXIMUM_LINES ] = array(
			'test.txt',
		);

		ob_start();

		vipgoci_run_scan_log_skipped_files(
			$this->options,
			$this->results,
			$this->prs_implicated
		);

		$printed_output = ob_get_clean();

		$this->assertNotFalse(
			strpos(
				$printed_output,
				'"Too large file(s) was\/were detected during analysis:'
			),
			'No message was logged about too large files'
		);
	}
}
