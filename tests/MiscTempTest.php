<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );
require_once( __DIR__ . '/../statistics.php' );


final class MiscTempTest extends TestCase {
	/**
	 * @covers ::vipgoci_save_temp_file
	 */
	public function testTempFile1() {
		$file_name_prefix = 'myfilename1';
		$file_name_extension = null;
		$file_contents = 'mycontentsofthefile1' . PHP_EOL;

		$temp_file_name = vipgoci_save_temp_file(
			$file_name_prefix,
			$file_name_extension,
			$file_contents
		);

		$this->assertNotEquals(
			$temp_file_name,
			false
		);

        
		$temp_file_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$temp_file_contents = file_get_contents(
			$temp_file_name
		);

		$this->assertEquals(
			$temp_file_extension,
			$file_name_extension
		);

		$this->assertEquals(
			$file_contents,
			$temp_file_contents
		);

		unlink( $temp_file_name );
	}

	/**
	 * @covers ::vipgoci_save_temp_file
	 */
	public function testTempFile2() {
		$file_name_prefix = 'myfilename2';
		$file_name_extension = 'txt';
		$file_contents = 'mycontentsofthefile2' . PHP_EOL;

		$temp_file_name = vipgoci_save_temp_file(
			$file_name_prefix,
			$file_name_extension,
			$file_contents
		);

		$this->assertNotEquals(
			$temp_file_name,
			false
		);

        
		$temp_file_extension = pathinfo(
			$temp_file_name,
			PATHINFO_EXTENSION
		);

		$temp_file_contents = file_get_contents(
			$temp_file_name
		);

		$this->assertEquals(
			$temp_file_extension,
			$file_name_extension
		);

		$this->assertEquals(
			$file_contents,
			$temp_file_contents
		);

		unlink( $temp_file_name );
	}
}
