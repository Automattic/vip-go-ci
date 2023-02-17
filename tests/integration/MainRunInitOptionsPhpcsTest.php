<?php
/**
 * Test vipgoci_run_init_options_phpcs().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Test vipgoci_run_init_options_phpcs function.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsPhpcsTest extends TestCase {
	/**
	 * Path to PHPCS script.
	 *
	 * @var $phpcs_path
	 */
	private string $phpcs_path = '';

	/**
	 * Options array.
	 *
	 * @var $options
	 */
	private array $options = array();

	/**
	 * Set up all variables.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';

		require_once __DIR__ . '/../unit/helper/IndicateTestId.php';

		$this->phpcs_path = vipgoci_unittests_get_config_value(
			'phpcs-scan',
			'phpcs-path',
			false
		);

		$this->options = array(
			'phpcs-path' => $this->phpcs_path,
		);
	}

	/**
	 * Clear all variables.
	 */
	protected function tearDown() :void {
		unset( $this->phpcs_path );
		unset( $this->options );
	}

	/**
	 * Test function. Check if PHPCS defaults are initialized.
	 *
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
				'phpcs-standards-to-ignore'               => null,
				'phpcs-sniffs-include'                    => null,
				'phpcs-sniffs-exclude'                    => null,
				'phpcs-runtime-set'                       => null,
				'phpcs-file-extensions'                   => null,
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
				'phpcs-standards-to-ignore'               => array(),
				'phpcs-sniffs-include'                    => array(),
				'phpcs-sniffs-exclude'                    => array(),
				'phpcs-runtime-set'                       => array(),
				'phpcs-file-extensions'                   => array( 'php', 'js', 'twig' ),
				'phpcs-skip-folders'                      => array(),
				'phpcs-severity'                          => 1,
				'phpcs-standard-file'                     => false,
				'phpcs-php-path'                          => null,
			),
			$this->options
		);
	}

	/**
	 * Test function. Check if PHPCS customizations are initialized.
	 *
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
			'phpcs-standards-to-ignore'               => 'myStandardToIgnore1,myStandardToIgnore2',
			'phpcs-sniffs-include'                    => 'Sniff1,Sniff2',
			'phpcs-sniffs-exclude'                    => 'Sniff3,Sniff4',
			'phpcs-runtime-set'                       => 'key1 value1,key2 value2',
			'phpcs-file-extensions'                   => 'php,js',
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
				'phpcs-standard'                          => array( 'WordPress', 'myStandard1' ),
				'phpcs-standards-to-ignore'               => array( 'myStandardToIgnore1', 'myStandardToIgnore2' ),
				'phpcs-sniffs-include'                    => array( 'Sniff1', 'Sniff2' ),
				'phpcs-sniffs-exclude'                    => array( 'Sniff3', 'Sniff4' ),
				'phpcs-runtime-set'                       => array(
					array( 'key1', 'value1' ),
					array( 'key2', 'value2' ),
				),
				'phpcs-file-extensions'                   => array( 'php', 'js' ),
				'phpcs-skip-folders'                      => array( 'myfolder1', 'myfolder2' ),
				'phpcs-severity'                          => 5,
				'phpcs-standard-file'                     => false,
				'phpcs-php-path'                          => 'php',
			),
			$this->options
		);
	}

	/**
	 * Test function. Check for vipgoci_sysexit() call
	 * when invalid options are used.
	 *
	 * @covers ::vipgoci_run_init_options_phpcs
	 */
	public function testRunInitOptionsPhpcsInvalid() :void {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array(),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		// Set options with invalid values.
		$this->options = array(
			'phpcs'                                   => 'true',
			'phpcs-skip-folders-in-repo-options-file' => 'true',
			'phpcs-skip-scanning-via-labels-allowed'  => 'true',
			'phpcs-path'                              => $this->phpcs_path,
			'phpcs-standard'                          => 'WordPress,myStandard1',
			'phpcs-standards-to-ignore'               => 'myStandard1', // Same value as in --phpcs-standard.
			'phpcs-sniffs-include'                    => 'Sniff1,Sniff2',
			'phpcs-sniffs-exclude'                    => 'Sniff3,Sniff4',
			'phpcs-runtime-set'                       => 'key1 value1,key2 value2',
			'phpcs-file-extensions'                   => 'php,js',
			'phpcs-skip-folders'                      => 'myfolder1,myfolder2',
			'phpcs-severity'                          => 5,
		);

		vipgoci_unittests_indicate_test_id( 'MainRunInitOptionsPhpcsTest' );

		ob_start();

		vipgoci_run_init_options_phpcs(
			$this->options
		);

		$printed_data = ob_get_contents();

		ob_end_clean();

		vipgoci_unittests_remove_indication_for_test_id( 'MainRunInitOptionsPhpcsTest' );

		if ( true === vipgoci_unittests_debug_mode_on() ) {
			echo $printed_data;
		}

		$printed_data_found = strpos(
			$printed_data,
			'--phpcs-standard and --phpcs-standards-to-ignore cannot share values'
		);

		$this->assertNotFalse( $printed_data_found );
	}
}
