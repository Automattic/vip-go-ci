<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );


final class MiscFileExtensionTest extends TestCase {
	/**
	 * @covers ::vipgoci_file_extension
	 */
	public function testFileExtension1() {
		$file_name = 'myfile.exe';

		$file_extension = vipgoci_file_extension(
			$file_name
		);

		$this->assertEquals(
			$file_extension,
			'exe'
		);
	}

	/**
	 * @covers ::vipgoci_file_extension
	 */
	public function testFileExtension2() {
		$file_name = 'myfile.EXE';

		$file_extension = vipgoci_file_extension(
			$file_name
		);

		$this->assertEquals(
			$file_extension,
			'exe'
		);
	}

	/**
	 * @covers ::vipgoci_file_extension
	 */
	public function testFileExtension3() {
		$file_name = 'myfile';

		$file_extension = vipgoci_file_extension(
			$file_name
		);

		$this->assertEquals(
			$file_extension,
			null
		);
	}
}
