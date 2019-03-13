<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscFilterFilePathTest extends TestCase {
	/**
	  * @covers ::vipgoci_filter_file_path
	  */
	public function testFilterFilePath1() {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'txt'
					)
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'ini'
					)
				)
			)
		);
	}


		/**
		  * @covers ::vipgoci_filter_file_path
		  */
	public function testFilterFilePath2() {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'txt', 'ini'
					)
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'file_extensions' => array(
						'ini', 'sys'
					)
				)
			)
		);
	}


	/**
	  * @covers ::vipgoci_filter_file_path
	  */
	public function testFilterFilePath3() {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder2',
					)
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder1',
					)
				)
			)
		);
	}


	/**
	  * @covers ::vipgoci_filter_file_path
	  */
	public function testFilterFilePath4() {
		$file_name = 'folder1/file1.txt';

		$this->assertTrue(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder2',
					),

					'file_extensions' => array(
						'txt', 'ini'
					)
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder1',
					),

					'file_extensions' => array(
						'ini'
					)
				)
			)
		);

		$this->assertFalse(
			vipgoci_filter_file_path(
				$file_name,
				array(
					'skip_folders' => array(
						'folder1',
					),

					'file_extensions' => array(
						'txt', 'ini'
					)
				)
			)
		);


	}



}
