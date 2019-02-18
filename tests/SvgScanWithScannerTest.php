<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SvgScanWithScannerTest extends TestCase {
	var $svg_scanner_path = '/home/phpunit/vip-go-ci-tools/vip-go-svg-sanitizer/svg-scanner.php';

	/**
	 * @covers ::vipgoci_svg_do_scan_with_scanner
	 */
	public function testScanner1() {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'svg-scan-with-scanner-test1.svg'
		);

		file_put_contents(
			$temp_file_name,
			'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" height="600px" id="Layer_1" width="600px" x="0px" y="0px" xml:space="preserve">
			    <a href="javascript:alert(2)">test 1</a>
			    <a xlink:href="javascript:alert(2)">test 2</a>
			    <a href="#test3">test 3</a>
			    <a xlink:href="#test">test 4</a>

			    <a href="data:data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' onload=\'alert(88)\'%3E%3C/svg%3E">test 5</a>
			    <a xlink:href="data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' onload=\'alert(88)\'%3E%3C/svg%3E">test 6</a>
			</svg>'
		);


		$scanner_results_json = vipgoci_svg_do_scan_with_scanner(
			$this->svg_scanner_path,
			$temp_file_name
		);

		$scanner_results = json_decode(
			$scanner_results_json,
			true
		);

		$temp_file_name_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$scanner_results_expected = json_decode(
			'{"totals":{"errors":4,"warnings":0,"fixable":0},"files":{"\/tmp\/svg-scan-with-scanner-test1.' . $temp_file_name_extension . '":{"errors":4,"messages":[{"message":"Suspicious attribute \'href\'","line":8},{"message":"Suspicious attribute \'href\'","line":7},{"message":"Suspicious attribute \'href\'","line":3},{"message":"Suspicious attribute \'href\'","line":2}]}}}',
			true
		);

		unlink(
			$temp_file_name
		);

		$this->assertEquals(
			$scanner_results,
			$scanner_results_expected
		);
	}


	/**
	 * @covers ::vipgoci_svg_do_scan_with_scanner
	 */
	public function testScanner2() {
		$temp_file_name = tempnam(
			sys_get_temp_dir(),
			'svg-scan-with-scanner-test2.svg'
		);

		file_put_contents(
			$temp_file_name,
			'<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" height="600px" id="Layer_1" width="600px" x="0px" y="0px" xml:space="preserve">
			    <a>test 1</a>
			    <a>test 2</a>
			    <a>test 3</a>
			    <a>test 4</a>

			    <a xmlns=\'http://www.w3.org/2000/svg\'>test 5</a>
			    <a xmlns=\'http://www.w3.org/2000/svg\'>test 6</a>
			</svg>'
		);

		$scanner_results_json = vipgoci_svg_do_scan_with_scanner(
			$this->svg_scanner_path,
			$temp_file_name
		);

		$scanner_results = json_decode(
			$scanner_results_json,
			true
		);

		$temp_file_name_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$scanner_results_expected = json_decode(
			'{"totals":{"errors":0,"warnings":0,"fixable":0},"files":{"\/tmp\/svg-scan-with-scanner-test2.' . $temp_file_name_extension . '":{"errors":0,"messages":[]}}}',
			true
		);


		unlink(
			$temp_file_name
		);

		$this->assertEquals(
			$scanner_results,
			$scanner_results_expected
		);
	}
}
