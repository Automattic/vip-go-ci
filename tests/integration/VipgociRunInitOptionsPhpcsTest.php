<?php

namespace Vipgoci\Tests\Integration;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunInitOptionsPhpcsTest extends TestCase {
	protected function setUp() :void {
		$this->phpcs_path = vipgoci_unittests_get_config_value(
			'phpcs-scan',
			'phpcs-path',
			false
		);

		$this->options = array(
			'phpcs-path' => $this->phpcs_path, 
		);
	}

	protected function tearDown() :void {
		unset( $this->phpcs_path );
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_phpcs
	 */
	public function testRunInitOptionsPhpcsDefaults() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->options = array_merge(
			array(
				'phpcs'                                   => null,
				'phpcs-skip-folders-in-repo-options-file' => null,
				'phpcs-skip-scanning-via-labels-allowed'  => null,
				'phpcs-path'                              => null,
				'phpcs-standard'                          => null,
				'phpcs-sniffs-include'                    => null,
				'phpcs-sniffs-exclude'                    => null,
				'phpcs-runtime-set'                       => null,
				'phpcs-skip-folders'                      => null,
				'phpcs-severity'                          => null,
			),
			$this->options
		);

		vipgoci_run_init_options_phpcs(
			$this->options
		);

		$this->assertSame(
			array(
				'phpcs'                                   => false,
				'phpcs-skip-folders-in-repo-options-file' => false,
				'phpcs-skip-scanning-via-labels-allowed'  => false,
				'phpcs-path'                              => null,
				'phpcs-standard'                          => array( 'WordPress' ),
				'phpcs-sniffs-include'                    => array(),
				'phpcs-sniffs-exclude'                    => array(),
				'phpcs-runtime-set'                       => array(),
				'phpcs-skip-folders'                      => array(),
				'phpcs-severity'                          => 1,
				'phpcs-standard-file'                     => false,
			),
			$this->options
		);
	}

	/**
	 * @covers ::vipgoci_run_init_options_phpcs
	 */
	public function testRunInitOptionsPhpcsCustom() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$this->assertNotEmpty( $this->phpcs_path, 'No path found for PHPCS to test' );

		// Set options with custom settings.
		$this->options = array(
			'phpcs'                                   => 'true',
			'phpcs-skip-folders-in-repo-options-file' => 'true',
			'phpcs-skip-scanning-via-labels-allowed'  => 'true',
			'phpcs-path'                              => $this->phpcs_path,
			'phpcs-standard'                          => 'WordPress,myStandard1',
			'phpcs-sniffs-include'                    => 'Sniff1,Sniff2',
			'phpcs-sniffs-exclude'                    => 'Sniff3,Sniff4',
			'phpcs-runtime-set'                       => 'key1 value1,key2 value2',
			'phpcs-skip-folders'                      => 'myfolder1,myfolder2',
			'phpcs-severity'                          => 5,
		);


		vipgoci_run_init_options_phpcs(
			$this->options
		);

		$this->assertSame(
			array(
				'phpcs'                                   => true,
				'phpcs-skip-folders-in-repo-options-file' => true,
				'phpcs-skip-scanning-via-labels-allowed'  => true,
				'phpcs-path'                              => $this->phpcs_path,
				'phpcs-standard'                          => array('WordPress', 'myStandard1'),
				'phpcs-sniffs-include'                    => array('Sniff1', 'Sniff2'),
				'phpcs-sniffs-exclude'                    => array('Sniff3', 'Sniff4'),
				'phpcs-runtime-set'                       => array(
					array( 'key1', 'value1' ),
					array( 'key2', 'value2' )
				),
				'phpcs-skip-folders'                      => array('myfolder1', 'myfolder2'),
				'phpcs-severity'                          => 5,
				'phpcs-standard-file'                     => false,
			),
			$this->options
		);
	}
}
