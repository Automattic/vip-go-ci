<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanGetSniffsForStandard extends TestCase {
	var $phpcs_config = array(
		'phpcs-path'		=> null,
		'phpcs-standard'	=> null,
		'phpcs-sniffs-existing'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->phpcs_config
		);

		$this->phpcs_config['phpcs-sniffs-existing'] = explode(
			',',
			$this->phpcs_config['phpcs-sniffs-existing']
		);
	}

	protected function tearDown() {
		$this->phpcs_config = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_get_sniffs_for_standard
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->phpcs_config,
			array( ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		vipgoci_unittests_output_suppress();

		$phpcs_sniffs = vipgoci_phpcs_get_sniffs_for_standard(
			$this->phpcs_config['phpcs-path'],
			$this->phpcs_config['phpcs-standard']
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertNotEmpty(
			$this->phpcs_config['phpcs-sniffs-existing']
		);

		$this->assertNotEmpty(
			$phpcs_sniffs
		);

		foreach(
			$this->phpcs_config['phpcs-sniffs-existing']
				as $sniff_name
		) {
			$this->assertNotFalse(
				in_array(
					$sniff_name,
					$phpcs_sniffs,
					true
				)
			);
		}
	}
}
