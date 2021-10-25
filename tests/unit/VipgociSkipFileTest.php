<?php

declare( strict_types=1 );

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../defines.php' );
require_once( __DIR__ . './../../skip-file.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociSkipFileTest extends TestCase {

	/**
	 * @covers ::vipgoci_get_skipped_files
	 */
	public function testGetSkippedFilesWillReturnCorrectValue() {
		$validation_mock = array(
			'issues' => array( 'max-lines' => array( 'MyFailedClass1.php' ) ),
			'total'  => 1
		);

		$current_skipped_files_mock = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$skipped_files = vipgoci_get_skipped_files(
			$current_skipped_files_mock,
			$validation_mock
		);

		$expected_skipped_files = array(
			'total'  => 3,
			'issues' => array(
				'max-lines' => array(
					'MyFailedClass1.php',
					'MyFailedClass2.php',
					'MyFailedClass3.php',
				),
			),
		);

		sort( $expected_skipped_files['issues']['max-lines'] );
		sort( $skipped_files['issues']['max-lines'] );

		$this->assertSame(
			$expected_skipped_files,
			$skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_get_skipped_files
	 */
	public function testGetSkippedFilesWillReturnCorrectValueForTotal0() {
		$validation_mock = array(
			'issues' => array(),
			'total'  => 0
		);

		$current_skipped_files_mock = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$skipped_files = vipgoci_get_skipped_files(
			$current_skipped_files_mock,
			$validation_mock
		);

		$expected_skipped_files = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' )
			),
		);

		$this->assertSame(
			$expected_skipped_files,
			$skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_set_skipped_file
	 */
	public function testSetSkippedFilesWillSetCorrectValues(): void {
		$commid_id_mock       = 8;
		$commit_skipped_files = array(
			$commid_id_mock => array(
				'issues' => array(),
				'total'  => 0
			)
		);
		$validation_mock      = array(
			'total'  => 1,
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
				'total'  => 1
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
	public function testSetPRsImplicatedSkippedFilesWillSetCorrectValues(): void {
		$pr                   = new \stdClass();
		$pr->number           = 8;
		$prs_implicated       = array( 8 => $pr );
		$commit_skipped_files = array(
			8 => array(
				'issues' => array(),
				'total'  => 0
			)
		);
		$validation_mock      = array(
			'total'  => 1,
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
				'total'  => 1
			)
		);

		$this->assertSame(
			$expected_skipped_files,
			$commit_skipped_files
		);
	}

	/**
	 * @covers ::vipgoci_get_skipped_files_message
	 */
	public function testGetSkippedFilesMessage(): void {

		$skipped               = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' )
			),
			'total'  => 2
		);
		$skipped_files_message = vipgoci_get_skipped_files_message( $skipped, 15000 );

		$expected_skipped_files_message = '
**skipped-files**

Maximum number of lines exceeded (15000):
 - MyFailedClass.php
 - MyFailedClass2.php

Note that the above file(s) were not analyzed due to their length.';

		$this->assertSame( $expected_skipped_files_message, $skipped_files_message );
	}

	/**
	 * @covers ::vipgoci_get_skipped_files_message
	 */
	public function testGetSkippedFilesMessageWithNumberOfLinesExceededDifferentThanDefault() {

		$skipped               = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' )
			),
			'total'  => 2
		);
		$skipped_files_message = vipgoci_get_skipped_files_message( $skipped, 25000 );

		$expected_skipped_files_message = '
**skipped-files**

Maximum number of lines exceeded (25000):
 - MyFailedClass.php
 - MyFailedClass2.php

Note that the above file(s) were not analyzed due to their length.';

		$this->assertSame( $expected_skipped_files_message, $skipped_files_message );
	}

	/**
	 * @covers ::vipgoci_get_skipped_files_issue_message
	 */
	public function testGetSkippedFilesIssueMessage(): void {
		$affected_files_mock = array( 'MyFailedClass.php', 'MyFailedClass2.php' );

		$skipped_files_issue_message = vipgoci_get_skipped_files_issue_message(
			$affected_files_mock,
			'max-lines',
			15000
		);

		$expected_skipped_files_issue_message = 'Maximum number of lines exceeded (15000):
 - MyFailedClass.php
 - MyFailedClass2.php';

		$this->assertSame(
			$expected_skipped_files_issue_message,
			$skipped_files_issue_message
		);
	}

	/**
	 * @covers ::vipgo_skip_file_check_previous_pr_comments
	 */
	public function testVipgociVerifySkipFileMessageDuplication(): void {
		$comments_mock = $this->getSkippedFilesCommentsMock();
		$results_mock  = $this->getResultsMock();

		$result = vipgo_skip_file_check_previous_pr_comments(
			$results_mock[15],
			$comments_mock
		);

		$expected = array(
			'issues' =>
				array(
					'max-lines' =>
						array( 'tests1/myfile2.php' ),
				),
			'total'  => 1,
		);

		$this->assertSame(
			$result,
			$expected
		);
	}

	/**
	 * @covers ::vipgo_get_large_files_from_comment
	 */
	public function testGetLargeFilesFromComments(): void {
		$skippedFileCommentMock = $this->getSkippedFilesCommentMock();

		$result   = vipgo_get_large_files_from_comment( $skippedFileCommentMock );
		$expected = [
			'GoogleAtom.php',
			'MySuccessClass.php',
			'MySuccessClass2.php',
			'src/MySuccesClasss.php',
			'src/SyntaxError.php',
			'tests1/myfile1.php',
		];

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_large_files_from_comment
	 */
	public function testGetLargeFilesFromCommentsWillReturnEmptyWhenCommentIsNotAboutSkippedFiles(): void {
		$commentMock = $this->getCommentMock();

		$result   = vipgo_get_large_files_from_comment( $commentMock );
		$expected = array();

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_large_files_from_pr_comments
	 */
	public function testGetLargeFilesFromPRComments(): void {
		$commentsSkippedFilesMock = $this->getSkippedFilesCommentsMock();

		$result   = vipgo_get_large_files_from_pr_comments( $commentsSkippedFilesMock );
		$expected = [
			'GoogleAtom.php',
			'MySuccessClass.php',
			'MySuccessClass2.php',
			'src/MySuccesClasss.php',
			'src/SyntaxError.php',
			'tests1/myfile1.php',
		];

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_large_files_from_pr_comments
	 */
	public function testGetLargeFilesFromPRCommentsWhenCommentsAreNotAboutSkippedFilesWillReturnEmpty(): void {
		$commentsMock = $this->getCommentMock();

		$result   = vipgo_get_large_files_from_pr_comments( [ $commentsMock ] );
		$expected = array();

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @return array[]
	 */
	private function getResultsMock(): array {
		return array(
			15 => array(
				'issues' =>
					array(
						'max-lines' =>
							array(
								0 => 'GoogleAtom.php',
								1 => 'MySuccessClass.php',
								2 => 'MySuccessClass2.php',
								3 => 'src/MySuccesClasss.php',
								4 => 'src/SyntaxError.php',
								5 => 'tests1/myfile1.php',
								6 => 'tests1/myfile2.php',
							),
					),
				'total'  => 7,
			),
		);
	}

	/**
	 * @return array
	 */
	private function getSkippedFilesCommentsMock(): array {
		$mock = $this->getSkippedFilesCommentMock();

		return [ $mock ];
	}

	/**
	 * @return mixed
	 */
	private function getSkippedFilesCommentMock(): \stdClass {
		$mock       = $this->createMock( 'stdClass' );
		$mock->body =
			'**hashes-api**-scanning skipped
***

**skipped-files**

Maximum number of lines exceeded (3):
 - GoogleAtom.php
 - MySuccessClass.php
 - MySuccessClass2.php
 - src/MySuccesClasss.php
 - src/SyntaxError.php
 - tests1/myfile1.php

Note that the above file(s) were not analyzed due to their length.';

		return $mock;
	}

	/**
	 * @return mixed
	 */
	private function getCommentMock(): \stdClass {
		$commentMock       = $this->createMock( 'stdClass' );
		$commentMock->body =
			'**hashes-api**';

		return $commentMock;
	}
}
