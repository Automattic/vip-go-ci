<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanDoScanTest extends TestCase {
	var $options_phpcs = array(
		'phpcs-path'		=> null,
		'phpcs-standard'	=> null,
		'phpcs-severity'	=> null,
		'phpcs-runtime-set'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->options_phpcs
		);

		$this->options_phpcs['phpcs-sniffs-exclude'] = array(
		);
	}

	protected function tearDown() {
		$this->options_phpcs = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_do_scan
	 */
	public function testDoScanTest1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options_phpcs,
			array( 'phpcs-runtime-set' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$temp_file_contents = 
			'<?php' . PHP_EOL .
			'echo time();' . PHP_EOL .
			'echo "foo" . PHP_EOL;' . PHP_EOL .
			'echo esc_html( strip_tags("foo") ) . PHP_EOL;' . PHP_EOL
			. PHP_EOL;

		$temp_file_ext = 'php';

		$temp_file_path = vipgoci_save_temp_file(
			__FUNCTION__,
			$temp_file_ext,
			$temp_file_contents
		);

		vipgoci_unittests_output_suppress();

		$phpcs_res = vipgoci_phpcs_do_scan(
			$temp_file_path,
			$this->options_phpcs['phpcs-path'],
			$this->options_phpcs['phpcs-standard'],
			$this->options_phpcs['phpcs-sniffs-exclude'],
			$this->options_phpcs['phpcs-severity'],
			$this->options_phpcs['phpcs-runtime-set']
		);

		vipgoci_unittests_output_unsuppress();

		unlink( $temp_file_path );

		$this->assertEquals(
			'{"totals":{"errors":1,"warnings":1,"fixable":0},"files":{"' . addcslashes( $temp_file_path, '/' ) . '":{"errors":1,"warnings":1,"messages":[{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'time\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"type":"ERROR","line":2,"column":6},{"message":"`strip_tags()` does not strip CSS and JS in between the script and style tags. Use `wp_strip_all_tags()` to strip all tags.","source":"WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter","severity":5,"fixable":false,"type":"WARNING","line":4,"column":16}]}}}',
			$phpcs_res
		);
	}
}
