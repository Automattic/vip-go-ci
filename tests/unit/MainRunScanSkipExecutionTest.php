<?php
/**
 * Test function vipgoci_run_scan_skip_execution().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Check if skip execution functionality works.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_run_scan_skip_execution' )]
final class MainRunScanSkipExecutionTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up variable, indicate what test is running.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/helper/IndicateTestId.php';

		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../main.php';
		require_once __DIR__ . './../../log.php';

		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'MainRunScanSkipExecutionTest' );

		$this->options = array();
	}

	/**
	 * Remove the indication and clear variable.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunScanSkipExecutionTest' );

		unset( $this->options );
	}

	/**
	 * Check if vipgoci_run_scan_skip_execution() attempts to
	 * exit.
	 *
	 * @return void
	 */
	public function testRunScanSkipExecution() :void {
		$this->options['skip-execution'] = true;

		ob_start();

		vipgoci_run_scan_skip_execution(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if expected string was printed
		 */
		$printed_data_found = strpos(
			$printed_data,
			'"Skipping scanning entirely, as determined by configuration";'
		);

		$this->assertNotFalse(
			$printed_data_found
		);
	}

	/**
	 * Check if vipgoci_run_scan_skip_execution() returns
	 * with exit-status.
	 *
	 * @return void
	 */
	public function testRunScanDoesNotSkipExecution() :void {
		$this->options['skip-execution'] = false;

		ob_start();

		vipgoci_run_scan_skip_execution(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		/*
		 * Check if nothing was printed.
		 */
		$this->assertEmpty(
			$printed_data
		);
	}

	/** A real exit must prevent work immediately after the skip gate. */
	public function testSkipExecutionTerminatesChildProcess(): void {
		list( $status, $output ) = $this->runSkipGateInChild( true );
		$this->assertSame( VIPGOCI_EXIT_NORMAL, $status );
		$this->assertStringContainsString( 'Skipping scanning entirely, as determined by configuration', $output );
		$this->assertStringNotContainsString( 'downstream-sentinel', $output );
	}

	/** The disabled gate must return and allow downstream work. */
	public function testDisabledSkipExecutionReachesDownstreamWork(): void {
		list( $status, $output ) = $this->runSkipGateInChild( false );
		$this->assertSame( 17, $status );
		$this->assertSame( 'downstream-sentinel', $output );
	}

	/**
	 * Run without PHPUnit's indication that makes vipgoci_sysexit() return.
	 *
	 * @param bool $skip Whether to terminate at the gate.
	 * @return array Child exit status and complete captured output.
	 */
	private function runSkipGateInChild( bool $skip ): array {
		$script = '';
		foreach ( array( 'defines', 'main', 'log' ) as $module ) {
			$script .= 'require ' . var_export( __DIR__ . '/../../' . $module . '.php', true ) . ';';
		}
		$script .= 'if (function_exists("vipgoci_unittests_check_indication_for_test_id")) { exit(99); }';
		$script .= '$vipgoci_debug_level = 0;';
		$script .= '$options = array("skip-execution" => ' . ( $skip ? 'true' : 'false' ) . ');';
		$script .= 'vipgoci_run_scan_skip_execution($options);';
		$script .= 'echo "downstream-sentinel"; exit(17);';
		exec( escapeshellarg( PHP_BINARY ) . ' -r ' . escapeshellarg( $script ) . ' 2>&1', $output, $status );
		return array( $status, implode( "\n", $output ) );
	}
}
