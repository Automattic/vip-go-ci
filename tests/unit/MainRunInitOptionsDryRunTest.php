<?php
/**
 * Test function vipgoci_run_init_options_dry_run().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

/**
 * Check that --dry-run accepts valid values in both forms, and that
 * a --dry-run without a valid value, or not parsed by getopt(), is
 * rejected, even when a value was given earlier or read from the
 * environment.
 */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_run_init_options_dry_run' )]
final class MainRunInitOptionsDryRunTest extends TestCase {
	/**
	 * Require files and set indication.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/helper/IndicateTestId.php';

		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../log.php';
		require_once __DIR__ . '/../../http-functions.php';

		/*
		 * Indicate that this particular test is running,
		 * needed so that vipgoci_sysexit() can return
		 * instead of exiting. See the function itself.
		 */
		vipgoci_unittests_indicate_test_id( 'MainRunInitOptionsDryRunTest' );
	}

	/**
	 * Remove indication.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		vipgoci_unittests_remove_indication_for_test_id( 'MainRunInitOptionsDryRunTest' );
	}

	/**
	 * Arguments, options as parsed by getopt() or read from
	 * the environment, and expected results.
	 *
	 * @return array Test cases.
	 */
	public static function dataDryRun() :array {
		return array(
			'not set'              => array( array(), array(), false ),
			'equals true'          => array( array( '--dry-run=true' ), array( 'dry-run' => 'true' ), true ),
			'separate true'        => array( array( '--dry-run', 'true' ), array( 'dry-run' => 'true' ), true ),
			'separate false'       => array( array( '--dry-run', 'false' ), array( 'dry-run' => 'false' ), false ),
			'invalid value'        => array( array( '--dry-run=yes' ), array( 'dry-run' => 'yes' ), null ),
			'empty value'          => array( array( '--dry-run=' ), array( 'dry-run' => '' ), null ),
			'trailing bare'        => array( array( '--dry-run' ), array(), null ),
			'repeated, bare'       => array( array( '--dry-run=false', '--dry-run' ), array( 'dry-run' => 'false' ), null ),
			'repeated, empty'      => array( array( '--dry-run=false', '--dry-run=' ), array( 'dry-run' => 'false' ), null ),
			'environment and bare' => array( array( '--dry-run' ), array( 'dry-run' => 'false' ), null ),
			'after end-of-options' => array( array( '--', '--dry-run=true' ), array(), null ),
			'after -- as a value'  => array( array( '--phpcs-standard', '--', 'stray', '--dry-run=true' ), array( 'phpcs-standard' => '--' ), null ),
			'after stray argument' => array( array( '--repo-owner=a', 'stray', '--dry-run=true' ), array( 'repo-owner' => 'a' ), null ),
			'as another value'     => array( array( '--phpcs-standard', '--dry-run=true' ), array( 'phpcs-standard' => '--dry-run=true' ), null ),
		);
	}

	/**
	 * Check the resulting option value, or that a usage error is printed.
	 *
	 * @param array     $args           Command-line arguments.
	 * @param array     $options        Options as parsed by getopt() or read from the environment.
	 * @param null|bool $expected_value Expected value of the option, null if a usage error is expected.
	 *
	 * @return void
	 */
	#[DataProvider( 'dataDryRun' )]
	public function testDryRun(
		array $args,
		array $options,
		null|bool $expected_value
	) :void {
		$GLOBALS['argv'] = array_merge( array( 'vip-go-ci.php' ), $args );

		ob_start();

		vipgoci_run_init_options_dry_run( $options );

		$printed_data = ob_get_clean();

		$this->assertSame(
			null === $expected_value,
			str_contains( $printed_data, 'Usage: Parameter --dry-run ' ),
			$printed_data
		);

		if ( null !== $expected_value ) {
			$this->assertSame( $expected_value, $options['dry-run'] );
		}
	}
}
