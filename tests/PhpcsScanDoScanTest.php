<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class PhpcsScanDoScanTest extends TestCase {
	var $phpcs_config = array(
		'phpcs_path'		=> null,
		'phpcs_standard'	=> null,
		'phpcs_sniffs_exclude'	=> null,
		'phpcs_severity'	=> null,
		'phpcs_runtime_set'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'phpcs-scan',
			$this->phpcs_config
		);
	}

	protected function tearDown() {
		$this->phpcs_config = null;
	}

	/**
	 * @covers ::vipgoci_phpcs_do_scan
	 */
	public function testDoScanTest1() {
		if (
			( empty( $this->phpcs_config['phpcs_path'] ) ) ||
			( empty( $this->phpcs_config['phpcs_standard'] ) ) ||
			( empty( $this->phpcs_config['phpcs_severity'] ) )
		) {
			$this->markTestSkipped(
				'Must configure PHPCS first'
			);

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

		$phpcs_res = vipgoci_phpcs_do_scan(
			$temp_file_path,
			$this->phpcs_config['phpcs_path'],
			$this->phpcs_config['phpcs_standard'],
			$this->phpcs_config['phpcs_sniffs_exclude'],
			$this->phpcs_config['phpcs_severity'],
			$this->phpcs_config['phpcs_runtime_set']
		);

		unlink( $temp_file_path );

		$temp_file_name = pathinfo(
			$temp_file_path,
			PATHINFO_FILENAME
		);

		$temp_file_name .= '.' . $temp_file_ext;

		$this->assertEquals(
			$phpcs_res,
			'{"totals":{"errors":1,"warnings":1,"fixable":0},"files":{"\/tmp\/' . $temp_file_name . '":{"errors":1,"warnings":1,"messages":[{"message":"All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'time\'.","source":"WordPress.Security.EscapeOutput.OutputNotEscaped","severity":5,"fixable":false,"type":"ERROR","line":2,"column":6},{"message":"`strip_tags()` does not strip CSS and JS in between the script and style tags. Use `wp_strip_all_tags()` to strip all tags.","source":"WordPressVIPMinimum.Functions.StripTags.StripTagsOneParameter","severity":5,"fixable":false,"type":"WARNING","line":4,"column":16}]}}}'
		);
	}
}
