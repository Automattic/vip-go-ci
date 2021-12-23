<?php

declare( strict_types=1 );

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . './../../defines.php';
require_once __DIR__ . './../../skip-file.php';

use PHPUnit\Framework\TestCase;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociSkipFileTest extends TestCase {

	public $validationMessagePrefix = 'Maximum number of lines exceeded (15000):' . PHP_EOL . ' - ';

	/**
	 * @covers ::vipgoci_get_skipped_files
	 */
	public function testGetSkippedFilesWillReturnCorrectValue(): void {
		$validation_mock = array(
			'issues' => array( 'max-lines' => array( 'MyFailedClass1.php' ) ),
			'total'  => 1,
		);

		$current_skipped_files_mock = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' ),
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
	public function testGetSkippedFilesWillReturnCorrectValueForTotal0(): void {
		$validation_mock = array(
			'issues' => array(),
			'total'  => 0,
		);

		$current_skipped_files_mock = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' ),
			),
		);

		$skipped_files = vipgoci_get_skipped_files(
			$current_skipped_files_mock,
			$validation_mock
		);

		$expected_skipped_files = array(
			'total'  => 2,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass2.php', 'MyFailedClass3.php' ),
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
				'total'  => 0,
			),
		);
		$validation_mock      = array(
			'total'  => 1,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php' ),
			),
		);

		vipgoci_set_skipped_file(
			$commit_skipped_files,
			$validation_mock,
			$commid_id_mock
		);

		$expected_skipped_files = array(
			'8' => array(
				'issues' => array(
					'max-lines' => array( 'MyFailedClass.php' ),

				),
				'total'  => 1,
			),
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
		$pr                   = new stdClass();
		$pr->number           = 8;
		$prs_implicated       = array( 8 => $pr );
		$commit_skipped_files = array(
			8 => array(
				'issues' => array(),
				'total'  => 0,
			),
		);
		$validation_mock      = array(
			'total'  => 1,
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php' ),
			),
		);

		vipgoci_set_prs_implicated_skipped_files(
			$prs_implicated,
			$commit_skipped_files,
			$validation_mock
		);

		$expected_skipped_files = array(
			'8' => array(
				'issues' => array(
					'max-lines' => array( 'MyFailedClass.php' ),

				),
				'total'  => 1,
			),
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

		$skipped = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' ),
			),
			'total'  => 2,
		);

		$skipped_files_message = vipgoci_get_skipped_files_message( $skipped, $this->validationMessagePrefix );

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
	public function testGetSkippedFilesMessageWithNumberOfLinesExceededDifferentThanDefault(): void {
		$skipped                 = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' ),
			),
			'total'  => 2,
		);
		$validationMessagePrefix = 'Maximum number of lines exceeded (25000):' . PHP_EOL . ' - ';
		$skipped_files_message   = vipgoci_get_skipped_files_message( $skipped, $validationMessagePrefix );

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
		$affected_files_mock         = array( 'MyFailedClass.php', 'MyFailedClass2.php' );
		$skipped_files_issue_message = vipgoci_get_skipped_files_issue_message(
			$affected_files_mock,
			$this->validationMessagePrefix
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
			$comments_mock,
			$this->validationMessagePrefix
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
	 * @covers ::vipgo_skip_file_check_previous_pr_comments
	 * @dataProvider vipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZeroProvider
	 */
	public function testVipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZero( array $skipped_files_result, array $comments ): void {

		$result = vipgo_skip_file_check_previous_pr_comments(
			$skipped_files_result,
			$comments,
			$this->validationMessagePrefix
		);

		$this->assertSame(
			$result,
			$skipped_files_result,
		);
	}

	/**
	 * @return array[]
	 */
	public function vipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZeroProvider(): array {
		return array(
			array( array( 'total' => 0 ), array( 'any' ) ),
			array( array( 'total' => 2 ), array() ),
			array(
				array(
					'issues' => array( 'max-lines' => array( 'test.php' ) ),
					'total'  => 1,
				),
				array( $this->getCommentMock() ),
			),
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_from_comment
	 */
	public function testGetLargeFilesFromComments(): void {
		$skippedFileCommentMock = $this->getSkippedFilesCommentMock();
		$result                 = vipgo_get_skipped_files_from_comment( $skippedFileCommentMock, $this->validationMessagePrefix );

		$expected = array(
			'GoogleAtom.php',
			'MySuccessClass.php',
			'MySuccessClass2.php',
			'src/MySuccesClasss.php',
			'src/SyntaxError.php',
			'tests1/myfile1.php',
		);

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_from_comment
	 */
	public function testGetSingleLargeFilesFromComments(): void {
		$skippedFileCommentMock = $this->getSkippedSingleFileCommentMock();

		$result   = vipgo_get_skipped_files_from_comment( $skippedFileCommentMock, $this->validationMessagePrefix );
		$expected = array(
			'file-10.php',
		);

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_from_comment
	 */
	public function testGetLargeFilesFromCommentsWillReturnEmptyWhenCommentIsNotAboutSkippedFiles(): void {
		$commentMock = $this->getCommentMock();

		$result   = vipgo_get_skipped_files_from_comment( $commentMock, $this->validationMessagePrefix );
		$expected = array();

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_from_pr_comments
	 */
	public function testGetLargeFilesFromPRComments(): void {
		$commentsSkippedFilesMock = $this->getSkippedFilesCommentsMock();

		$result   = vipgo_get_skipped_files_from_pr_comments( $commentsSkippedFilesMock, $this->validationMessagePrefix );
		$expected = array(
			'GoogleAtom.php',
			'MySuccessClass.php',
			'MySuccessClass2.php',
			'src/MySuccesClasss.php',
			'src/SyntaxError.php',
			'tests1/myfile1.php',
		);

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_message_from_comment()
	 */
	public function testGetLargeFilesMessageFromPRComment(): void {
		$skippedFileCommentMock = $this->getSkippedSingleFileCommentMock();
		$result                 = vipgo_get_skipped_files_message_from_comment( $skippedFileCommentMock->body, $this->validationMessagePrefix );
		$expected               = 'file-10.php';

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_message_from_comment()
	 * @dataProvider getLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFilesProvider
	 */
	public function testGetLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFiles( string $comment ): void {

		$result   = vipgo_get_skipped_files_message_from_comment( $comment, $this->validationMessagePrefix );
		$expected = '';

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * @return string[][]
	 */
	public function getLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFilesProvider(): array {
		return array(
			array( 'any' ),
			array( PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG ),
			array( '**skipped-files** ANY' . PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG ),
		);
	}

	/**
	 * @covers ::vipgo_get_skipped_files_from_pr_comments
	 */
	public function testGetLargeFilesFromPRCommentsWhenCommentsAreNotAboutSkippedFilesWillReturnEmpty(): void {
		$commentsMock = $this->getCommentMock();
		$result       = vipgo_get_skipped_files_from_pr_comments( array( $commentsMock ), $this->validationMessagePrefix );
		$expected     = array();

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

		return array( $mock );
	}

	/**
	 * @return stdClass
	 */
	private function getSkippedSingleFileCommentMock(): stdClass {
		$mock       = $this->createMock( 'stdClass' );
		$mock->body = '
**skipped-files**

Maximum number of lines exceeded (15000):
 - file-10.php

Note that the above file(s) were not analyzed due to their length.

***

This bot provides automated PHP linting and [PHPCS scanning](https://docs.wpvip.com/technical-references/code-review/phpcs-report/). For more information about the bot and available customizations, see [our documentation](https://docs.wpvip.com/technical-references/code-review/vip-code-analysis-bot/).';

		return $mock;
	}

	/**
	 * @return mixed
	 */
	private function getSkippedFilesCommentMock(): stdClass {
		$mock       = $this->createMock( 'stdClass' );
		$mock->body =
			'**hashes-api**-scanning skipped
***

**skipped-files**

Maximum number of lines exceeded (15000):
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
	private function getCommentMock(): stdClass {
		$commentMock       = $this->createMock( 'stdClass' );
		$commentMock->body =
			'**hashes-api**';

		return $commentMock;
	}
}
