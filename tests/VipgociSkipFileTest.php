<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class VipgociSkipFileTest extends TestCase {
	/**
	 * @covers ::vipgoci_get_skipped_files
	 */
	public function testGetSkippedFilesWillReturnCorrectValue() {
		$validation_mock = array(
			'issues' => array( 'max-lines' => array( 'MyFailedClass1.php' ) ),
			'total' => 1
		);

		$current_skipped_files_mock = array(
			'total' => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$skipped_files = vipgoci_get_skipped_files(
			$current_skipped_files_mock,
			$validation_mock
		);

		$expected_skipped_files = array(
				'issues' => array(
                    'max-lines' => array(
                        'MyFailedClass1.php',
	                    'MyFailedClass2.php',
	                    'MyFailedClass3.php'
                    )
				),
				'total' => 3
		);

		sort( $expected_skipped_files[ 'issues' ][ 'max-lines' ] );
		sort( $skipped_files[ 'issues' ][ 'max-lines' ] );

		$this->assertEquals(
			$expected_skipped_files,
			$skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_get_skipped_files
	 */
	public function testGetSkippedFilesWillReturnCorrectValueForTotal0() {
		$validation_mock = array(
			'issues' => array(), 'total' => 0
		);

		$current_skipped_files_mock = array(
			'total' => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$skipped_files = vipgoci_get_skipped_files(
			$current_skipped_files_mock,
			$validation_mock
		);

		$expected_skipped_files = array(
			'total' => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$this->assertEquals(
			$expected_skipped_files,
			$skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_set_skipped_file
	 */
	public function testSetSkippedFilesWillSetCorrectValues() {
		$commid_id_mock = 8;
		$commit_skipped_files = array(
			$commid_id_mock  => array(
				'issues' => array(),
				'total' => 0
	        )
		);
		$validation_mock = array(
			'total' => 1,
            'issues' => array(
				'max-lines' => array( 'MyFailedClass.php' )
			)
		);

		vipgoci_set_skipped_file(
			$commit_skipped_files,
			$validation_mock,
			$commid_id_mock
		);

		$expected_skipped_files = array(
			'8' => array(
				'issues' => array(
					'max-lines' => array( 'MyFailedClass.php' )

				),
				'total' => 1
			)
		);

		$this->assertSame(
			$expected_skipped_files,
			$commit_skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_set_prs_implicated_skipped_files
	 */
	public function testSetPRsImplicatedSkippedFilesWillSetCorrectValues() {
		$pr = new stdClass();
		$pr->number = 8;
		$prs_implicated = array( 8 => $pr);
		$commit_skipped_files = array(
			8  => array(
				'issues' => array(),
				'total' => 0
			)
		);
		$validation_mock = array(
			'total' => 1,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php' )
			)
		);

		vipgoci_set_prs_implicated_skipped_files(
			$prs_implicated,
			$commit_skipped_files,
			$validation_mock
		);

		$expected_skipped_files = array(
			'8' => array(
				'issues' => array(
					'max-lines' => array( 'MyFailedClass.php' )

				),
				'total' => 1
			)
		);

		$this->assertSame(
			$expected_skipped_files,
			$commit_skipped_files
		);
	}
}
