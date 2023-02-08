<?php
/**
 * Test various skipped files functionality.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class SkipFileTestFunctionalityTest extends TestCase {
	/**
	 * Constant mocked comment.
	 *
	 * @var $COMMENT_MOCK
	 */
	private const COMMENT_MOCK = '**test-api**';

	/**
	 * Message indicating maximum number of lines exceeded.
	 *
	 * @var $VALIDATION_MESSAGE_PREFIX
	 */
	private const VALIDATION_MESSAGE_PREFIX = 'Maximum number of lines exceeded (15000):' . PHP_EOL . ' - ';

	/**
	 * Returns mocked comment.
	 *
	 * @var $SKIPPED_FILES_COMMENT_MOCKED
	 */
	private const SKIPPED_FILES_COMMENT_MOCKED =
		'**test-api**-scanning skipped' . PHP_EOL .
		'***' . PHP_EOL .
		PHP_EOL .
		'**skipped-files**' . PHP_EOL .
		PHP_EOL .
		'Maximum number of lines exceeded (15000):' . PHP_EOL .
		' - GoogleAtom.php' . PHP_EOL .
		' - MySuccessClass.php' . PHP_EOL .
		' - MySuccessClass2.php' . PHP_EOL .
		' - src/MySuccesClasss.php' . PHP_EOL .
		' - src/SyntaxError.php' . PHP_EOL .
		' - tests1/myfile1.php' . PHP_EOL .
		PHP_EOL .
		'Note that the above file(s) were not analyzed due to their length.';

	/**
	 * Returns mocked comment.
	 *
	 * @var SKIPPED_SINGLE_FILE_COMMENT_MOCK
	 */
	private const SKIPPED_SINGLE_FILE_COMMENT_MOCK =
		'**skipped-files**' . PHP_EOL .
		PHP_EOL .
		'Maximum number of lines exceeded (15000):' . PHP_EOL .
		' - file-10.php' . PHP_EOL .
		PHP_EOL .
		'Note that the above file(s) were not analyzed due to their length.' . PHP_EOL .
		PHP_EOL .
		'***' . PHP_EOL .
		PHP_EOL .
		'This bot provides automated PHP linting and [PHPCS scanning](https://docs.wpvip.com/technical-references/code-review/phpcs-report/). For more information about the bot and available customizations, see [our documentation](https://docs.wpvip.com/technical-references/code-review/vip-code-analysis-bot/).';

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . './../../defines.php';
		require_once __DIR__ . './../../skip-file.php';
	}

	/**
	 * Test getting skipped files with correct value.
	 *
	 * @covers ::vipgoci_get_skipped_files
	 *
	 * @return void
	 */
	public function testGetSkippedFilesWillReturnCorrectValue() :void {
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
	 * Test getting skipped files.
	 *
	 * @covers ::vipgoci_get_skipped_files
	 *
	 * @return void
	 */
	public function testGetSkippedFilesWillReturnCorrectValueForTotal0() :void {
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
	 * Test setting skipped files.
	 *
	 * @covers ::vipgoci_set_skipped_file
	 *
	 * @return void
	 */
	public function testSetSkippedFilesWillSetCorrectValues() :void {
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
	 * Test setting skipped files for implicated PRs.
	 *
	 * @covers ::vipgoci_set_prs_implicated_skipped_files
	 *
	 * @return void
	 */
	public function testSetPRsImplicatedSkippedFilesWillSetCorrectValues() :void {
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
	 * Test getting skipped file message.
	 *
	 * @covers ::vipgoci_get_skipped_files_message
	 *
	 * @return void
	 */
	public function testGetSkippedFilesMessage() :void {

		$skipped = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' ),
			),
			'total'  => 2,
		);

		$skipped_files_message = vipgoci_get_skipped_files_message( $skipped, self::VALIDATION_MESSAGE_PREFIX );

		$expected_skipped_files_message = '
**skipped-files**

Maximum number of lines exceeded (15000):
 - MyFailedClass.php
 - MyFailedClass2.php

Note that the above file(s) were not analyzed due to their length.';

		$this->assertSame( $expected_skipped_files_message, $skipped_files_message );
	}

	/**
	 * Test getting skipped file message.
	 *
	 * @covers ::vipgoci_get_skipped_files_message
	 *
	 * @return void
	 */
	public function testGetSkippedFilesMessageWithNumberOfLinesExceededDifferentThanDefault() :void {
		$skipped                   = array(
			'issues' => array(
				'max-lines' => array( 'MyFailedClass.php', 'MyFailedClass2.php' ),
			),
			'total'  => 2,
		);
		$validation_message_prefix = 'Maximum number of lines exceeded (25000):' . PHP_EOL . ' - ';
		$skipped_files_message     = vipgoci_get_skipped_files_message( $skipped, $validation_message_prefix );

		$expected_skipped_files_message = '
**skipped-files**

Maximum number of lines exceeded (25000):
 - MyFailedClass.php
 - MyFailedClass2.php

Note that the above file(s) were not analyzed due to their length.';

		$this->assertSame( $expected_skipped_files_message, $skipped_files_message );
	}

	/**
	 * Test getting skipped file message.
	 *
	 * @covers ::vipgoci_get_skipped_files_issue_message
	 *
	 * @return void
	 */
	public function testGetSkippedFilesIssueMessage() :void {
		$affected_files_mock         = array( 'MyFailedClass.php', 'MyFailedClass2.php' );
		$skipped_files_issue_message = vipgoci_get_skipped_files_issue_message(
			$affected_files_mock,
			self::VALIDATION_MESSAGE_PREFIX
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
	 * Test getting previous PR comments.
	 *
	 * @covers ::vipgoci_skip_file_check_previous_pr_comments
	 *
	 * @return void
	 */
	public function testVipgociVerifySkipFileMessageDuplication() :void {
		$comments_mock = $this->getSkippedFilesCommentsMock();
		$results_mock  = $this->getResultsMock();

		$result = vipgoci_skip_file_check_previous_pr_comments(
			$results_mock[15],
			$comments_mock,
			self::VALIDATION_MESSAGE_PREFIX
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
	 * Test getting previous PR comments.
	 *
	 * @param array $skipped_files_result Skipped files array.
	 * @param array $comments             Comments array.
	 *
	 * @covers ::vipgoci_skip_file_check_previous_pr_comments
	 *
	 * @dataProvider vipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZeroProvider
	 *
	 * @return void
	 */
	public function testVipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZero(
		array $skipped_files_result,
		array $comments
	) :void {
		$result = vipgoci_skip_file_check_previous_pr_comments(
			$skipped_files_result,
			$comments,
			self::VALIDATION_MESSAGE_PREFIX
		);

		$this->assertSame(
			$result,
			$skipped_files_result,
		);
	}

	/**
	 * Data provider array.
	 *
	 * @return array[]
	 */
	public function vipgociVerifySkipFileMessageDuplicationWillReturnOfCommentsOreIssuesResultsAreZeroProvider(): array {
		return array(
			array(
				array(
					'total' => 0,
				),
				array( 'any' ),
			),
			array(
				array(
					'total' => 2,
				),
				array(),
			),
			array(
				array(
					'issues' => array(
						'max-lines' => array( 'test.php' ),
					),
					'total'  => 1,
				),
				array(
					self::COMMENT_MOCK,
				),
			),
		);
	}

	/**
	 * Get files skipped from comment.
	 *
	 * @covers ::vipgoci_get_skipped_files_from_comment
	 *
	 * @return void
	 */
	public function testGetLargeFilesFromComments() :void {
		$skipped_file_comment_mock = self::SKIPPED_FILES_COMMENT_MOCKED;
		$result                    = vipgoci_get_skipped_files_from_comment( $skipped_file_comment_mock, self::VALIDATION_MESSAGE_PREFIX );

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
	 * Get files skipped from comment.
	 *
	 * @covers ::vipgoci_get_skipped_files_from_comment
	 *
	 * @return void
	 */
	public function testGetSingleLargeFilesFromComments() :void {
		$skipped_file_comment_mock = self::SKIPPED_SINGLE_FILE_COMMENT_MOCK;

		$result   = vipgoci_get_skipped_files_from_comment( $skipped_file_comment_mock, self::VALIDATION_MESSAGE_PREFIX );
		$expected = array(
			'file-10.php',
		);

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * Get skipped files from comment.
	 *
	 * @covers ::vipgoci_get_skipped_files_from_comment
	 *
	 * @return void
	 */
	public function testGetLargeFilesFromCommentsWillReturnEmptyWhenCommentIsNotAboutSkippedFiles() :void {
		$comment_mock = self::COMMENT_MOCK;

		$result   = vipgoci_get_skipped_files_from_comment( $comment_mock, self::VALIDATION_MESSAGE_PREFIX );
		$expected = array();

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * Get skipped files from PR comments.
	 *
	 * @covers ::vipgoci_get_skipped_files_from_pr_comments
	 *
	 * @return void
	 */
	public function testGetLargeFilesFromPRComments() :void {
		$comments_skipped_files_mock = $this->getSkippedFilesCommentsMock();

		$result   = vipgoci_get_skipped_files_from_pr_comments( $comments_skipped_files_mock, self::VALIDATION_MESSAGE_PREFIX );
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
	 * Get skipped files from comment.
	 *
	 * @covers ::vipgoci_get_skipped_files_message_from_comment
	 *
	 * @return void
	 */
	public function testGetLargeFilesMessageFromPRComment() :void {
		$skipped_file_comment_mock = self::SKIPPED_SINGLE_FILE_COMMENT_MOCK;
		$result                    = vipgoci_get_skipped_files_message_from_comment( $skipped_file_comment_mock, self::VALIDATION_MESSAGE_PREFIX );
		$expected                  = 'file-10.php';

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * Get skipped files from comment.
	 *
	 * @param string $comment Comment string.
	 *
	 * @covers ::vipgoci_get_skipped_files_message_from_comment
	 *
	 * @dataProvider getLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFilesProvider
	 *
	 * @return void
	 */
	public function testGetLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFiles(
		string $comment
	) :void {
		$result   = vipgoci_get_skipped_files_message_from_comment( $comment, self::VALIDATION_MESSAGE_PREFIX );
		$expected = '';

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * Data provider. Returns PR comments.
	 *
	 * @return string[][]
	 */
	public function getLargeFilesMessageFromPRCommentShouldReturnEmptyForCommentsWithNoSkippedFilesProvider(): array {
		// Needed, as define is needed before setUp() is called.
		require_once __DIR__ . './../../defines.php';

		return array(
			array( 'any' ),
			array( PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG ),
			array( '**skipped-files** ANY' . PHP_EOL . PHP_EOL . VIPGOCI_VALIDATION_MAXIMUM_DETAIL_MSG ),
		);
	}

	/**
	 * Test when PR comments are not about skipped files.
	 *
	 * @covers ::vipgoci_get_skipped_files_from_pr_comments
	 *
	 * @return void
	 */
	public function testGetLargeFilesFromPRCommentsWhenCommentsAreNotAboutSkippedFilesWillReturnEmpty() :void {
		$comments_mock = self::COMMENT_MOCK;
		$result        = vipgoci_get_skipped_files_from_pr_comments( array( $comments_mock ), self::VALIDATION_MESSAGE_PREFIX );
		$expected      = array();

		$this->assertSame(
			$expected,
			$result
		);
	}

	/**
	 * Return mocked results.
	 *
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
	 * Return mocked comment.
	 *
	 * @return array
	 */
	private function getSkippedFilesCommentsMock(): array {
		$mock = self::SKIPPED_FILES_COMMENT_MOCKED;

		return array( $mock );
	}
}
