<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanPossiblyUseNewStandardFileTest extends TestCase {
	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Original standard.
	 *
	 * @var $original_standard
	 */
	private array $original_standard = array();

	protected function setUp(): void {
		$this->original_standard = array( 'WordPress-VIP-Go' );

		$this->options                         = array();
		$this->options['phpcs']                = true;
		$this->options['phpcs-standard']       = $this->original_standard;
		$this->options['phpcs-standard-file']  = false;
		$this->options['phpcs-sniffs-include'] = array();
	}

	protected function tearDown(): void {
		if (
			( true === $this->options['phpcs-standard-file'] )
			&&
			( file_exists(
				$this->options['phpcs-standard'][0]
			) )
		) {
			unlink(
				$this->options['phpcs-standard'][0]
			);
		}

		unset( $this->original_standard );
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_phpcs_possibly_use_new_standard_file
	 */
	public function testPhpcsDisabledTest() {
		$this->options['phpcs'] = false;

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_possibly_use_new_standard_file(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertFalse(
			$this->options['phpcs-standard-file']
		);

		$this->assertFalse(
			isset( $this->options['phpcs-standard-original'] )
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_possibly_use_new_standard_file
	 */
	public function testDoNotUseNewstandardFileTest() {
		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_possibly_use_new_standard_file(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertFalse(
			$this->options['phpcs-standard-file']
		);

		$this->assertSame(
			$this->original_standard,
			$this->options['phpcs-standard']
		);

		$this->assertFalse(
			isset( $this->options['phpcs-standard-original'] )
		);
	}

	/**
	 * @covers ::vipgoci_phpcs_possibly_use_new_standard_file
	 */
	public function testDoUseNewstandardFileTest() {
		$this->options['phpcs-sniffs-include'] = array(
			'WordPress.DB.RestrictedFunctions'
		);

		vipgoci_unittests_output_suppress();

		vipgoci_phpcs_possibly_use_new_standard_file(
			$this->options
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertTrue(
			$this->options['phpcs-standard-file']
		);

		$this->assertNotEquals(
			$this->original_standard,
			$this->options['phpcs-standard']
		);

		$this->assertTrue(
			file_exists(
				$this->options['phpcs-standard'][0]
			)
		);

		$this->assertIsArray(
			$this->options['phpcs-standard-original']
		);

		$this->assertGreaterThanOrEqual(
			1,
			count( $this->options['phpcs-standard-original'] )
		);
	}
}
