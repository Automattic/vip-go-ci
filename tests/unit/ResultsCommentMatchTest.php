<?php
/**
 * Test vipgoci_results_comment_match().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ResultsCommentMatchTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../results.php';
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_results_comment_match
	 *
	 * @return void
	 */
	public function testCommentMatch1() :void {
		$prs_comments = array(
			'file-8.php:3'   => array(
				(object) array(
					'body' => ':no_entry_sign: **Error**: All output should be ..., found \'mysql_query\'.',
				),
				(object) array(
					'body' => ':no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				),
				(object) array(
					'body' => ':no_entry_sign: **Error**: Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped (*WordPressVIPMinimum.JS.InnerHTML.innerHTML*).',
				),
			),

			'file-10.php:3'  => array(
				(object) array(
					'body' => ':no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				),
			),

			'file-11.php:5'  => array(
				(object) array(
					'body' => ':no_entry_sign: **Error( severity 11 )**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				),
			),

			'file-12.php:70' => array(
				(object) array(
					'body' => ':no_entry_sign: **Error( severity 11 )**: /** cannot be used',
				),
			),

			// Do not test against these; they are here to make sure nothing bogus is matched.
			'file-8.php:90'  => array(
				(object) array(
					'body' => ':no_entry_sign: **Error**: All output should be run ..., found \'mysql_query\'.',
				),
			),

			'file-9.php:90'  => array(
				(object) array(
					'body' => ':no_entry_sign: **Error**: Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				),
			),
		);

		$this->assertTrue(
			vipgoci_results_comment_match(
				'file-8.php',
				3,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertTrue(
			vipgoci_results_comment_match(
				'file-8.php',
				3,
				'Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-8.php',
				3,
				'The extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-8.php',
				4,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-8.php',
				4,
				'Any HTML passed to `innerHTML` gets executed. Consider using `.textContent` or make sure that used variables are properly escaped',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-9.php',
				3,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		/*
		 * Test with severity level
		 */
		$this->assertTrue(
			vipgoci_results_comment_match(
				'file-11.php',
				5,
				'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-11.php',
				5,
				'Extension \'mysql_\' is deprecated since PHP 300 and removed since PHP 700; Use mysqli instead',
				$prs_comments
			)
		);

		$this->assertTrue(
			vipgoci_results_comment_match(
				'file-12.php',
				70,
				'/** cannot be used',
				$prs_comments
			)
		);

		$this->assertFalse(
			vipgoci_results_comment_match(
				'file-12.php',
				70,
				'/*** cannot be used',
				$prs_comments
			)
		);
	}
}
