<?php
/**
 * Test trusted, operator-only rebase approval configuration.
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

/** Validate opt-in configuration without enabling ordinary file approval. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_run_init_options_rebases' )]
final class MainRunInitOptionsRebasesTest extends TestCase {
	/**
	 * Set up.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/helper/MainRunInitOptionsAutoapprove.php';
	}

	/**
	 * Test disabled by default.
	 *
	 * @return void
	 */
	public function testDisabledByDefault(): void {
		$options = array();
		vipgoci_run_init_options_rebases( $options );
		$this->assertSame(
			array(
				'autoapprove-rebases'               => false,
				'autoapprove-rebases-reviewer-ids'  => array(),
				'autoapprove-rebases-base-branches' => array(),
			),
			$options
		);
	}

	/**
	 * Test explicit trust preserves case sensitive branches.
	 *
	 * @return void
	 */
	public function testExplicitTrustPreservesCaseSensitiveBranches(): void {
		$options = array(
			'autoapprove-rebases'               => 'true',
			'autoapprove-rebases-reviewer-ids'  => '42,123',
			'autoapprove-rebases-base-branches' => 'trunk,Release',
		);
		vipgoci_run_init_options_rebases( $options );
		$this->assertTrue( $options['autoapprove-rebases'] );
		$this->assertSame( array( '42', '123' ), $options['autoapprove-rebases-reviewer-ids'] );
		$this->assertSame( array( 'trunk', 'Release' ), $options['autoapprove-rebases-base-branches'] );
		$this->assertArrayNotHasKey( 'autoapprove', $options );
	}

	/**
	 * Invalid options.
	 *
	 * @return array Test cases.
	 */
	public static function invalidOptions(): array {
		return array(
			array( array( 'autoapprove-rebases' => 'yes' ) ),
			array( array( 'autoapprove-rebases' => 'true' ) ),
			array(
				array(
					'autoapprove-rebases'              => 'true',
					'autoapprove-rebases-reviewer-ids' => '42',
				),
			),
			array(
				array(
					'autoapprove-rebases'               => 'true',
					'autoapprove-rebases-base-branches' => 'trunk',
				),
			),
			array( array( 'autoapprove-rebases-reviewer-ids' => 'reviewer-name' ) ),
			array( array( 'autoapprove-rebases-reviewer-ids' => '0' ) ),
			array( array( 'autoapprove-rebases-reviewer-ids' => '42,-1' ) ),
		);
	}

	/**
	 * Test missing or invalid trust is rejected.
	 *
	 * @param array $options Options.
	 * @return void
	 */
	#[DataProvider( 'invalidOptions' )]
	public function testMissingOrInvalidTrustIsRejected( array $options ): void {
		$this->expectException( \ErrorException::class );
		vipgoci_run_init_options_rebases( $options );
	}
}
