<?php

declare( strict_types=1 );

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/helper/GitRepoDiffsFetch.php' );
require_once( __DIR__ . './../../defines.php' );
require_once( __DIR__ . './../../lint-scan.php' );

use PHPUnit\Framework\TestCase;
use stdClass;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociLintScanTest extends TestCase {

	/**
	 * @param int $prNumber
	 * @param string $fileName
	 * @param array $fileScanningResults
	 * @param array $submitExpected
	 * @param array $statsExpected
	 *
	 * @covers       vipgoci_set_file_issues_result
	 * @dataProvider setFileIssuesResultProvider
	 */
	public function testSetFileIssuesResult(
		int $prNumber,
		string $fileName,
		array $fileScanningResults,
		array $submitExpected,
		array $statsExpected
	): void {
		$commitIssuesStats  = $this->getStatsMock();
		$commitIssuesSubmit = $this->getSubmitMock();

		vipgoci_set_file_issues_result( $commitIssuesSubmit[ $prNumber ], $commitIssuesStats[30]['error'], $fileName, $fileScanningResults );

		$this->assertSame( $statsExpected, $commitIssuesStats[30] );
		$this->assertSame( $submitExpected, $commitIssuesSubmit[30] );
	}

	/**
	 * @covers       vipgoci_get_prs_modified_files
	 * @dataProvider vipgociGetPrsModifiedFilesProvider
	 */
	public function testVipgociGetPrsModifiedFiles( array $prsImplicated, array $expected ): void {
		$options['local-git-repo']     = 'any';
		$options['repo-owner']         = 'any';
		$options['repo-name']          = 'any';
		$options['token']              = 'any';
		$options['commit']             = 'any';
		$options['phpcs-skip-folders'] = 'any';

		$actual = vipgoci_get_prs_modified_files( $options, $prsImplicated );
		$this->assertSame( $expected, $actual );
	}

	/**
	 * @return array
	 * Provider for testVipgociGetPrsModifiedFiles test
	 */
	public function vipgociGetPrsModifiedFilesProvider(): array {
		return [
			array(
				array(
					30 => (object) array( 'base' => (object) array( 'sha' => 'any' ) ),
					28 => (object) array( 'base' => (object) array( 'sha' => 'any' ) ),
				),
				array(
					'all'            =>
						array(
							0 => 'File1.php',
							1 => 'File2.php',
							2 => 'src/File3.php',
							3 => 'src/File4.php',
						),
					'prs_implicated' =>
						array(
							30 =>
								array(
									0 => 'File1.php',
									1 => 'File2.php',
									2 => 'src/File3.php',
									3 => 'src/File4.php',
								),
							28 =>
								array(
									0 => 'File1.php',
									1 => 'File2.php',
									2 => 'src/File3.php',
									3 => 'src/File4.php',
								),
						),
				)
			),
		];
	}

	/**
	 * @return array[]
	 * Data provider to support testSetFileIssuesResult test
	 * returns:
	 * PR number, file name, scanning results, expected_submit, expected_stats
	 */
	public function setFileIssuesResultProvider(): array {
		$submitExpected      = array(
			0 =>
				array(
					'type'      => 'lint',
					'file_name' => 'MySuccessClass.php',
					'file_line' => 15,
					'issue'     =>
						array(
							'message'  => 'syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),
		);
		$statsExpected       = array(
			'error'   => 1,
			'warning' => 0,
			'info'    => 0
		);
		$fileScanningResults = array(
			15 =>
				array(
					0 =>
						array(
							'message'  => 'syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),
		);

		// File with 1 line with issues && 2 issues in the same line
		$submitExpected1x2      = array(
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 15,
				'issue'     =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 15,
				'issue'     =>
					array(
						'message'  => '2 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
		);
		$statsExpected1x2       = array(
			'error'   => 2,
			'warning' => 0,
			'info'    => 0
		);
		$fileScanningResults1x2 = array(
			15 => array(
				0 =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
				1 =>
					array(
						'message'  => '2 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
		);

		// File with 2 issues in different lines
		$submitExpected2x1      = array(
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 15,
				'issue'     =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 21,
				'issue'     =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
		);
		$statsExpected2x1       = array(
			'error'   => 2,
			'warning' => 0,
			'info'    => 0
		);
		$fileScanningResults2x1 = array(
			15 =>
				array(
					0 =>
						array(
							'message'  => '1 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),
			21 =>
				array(
					0 =>
						array(
							'message'  => '1 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),
		);

		// File with 2 lines with 2 issues each line
		$submitExpected2x2      = array(
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 15,
				'issue'     =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 15,
				'issue'     =>
					array(
						'message'  => '2 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 21,
				'issue'     =>
					array(
						'message'  => '1 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
			array(
				'type'      => 'lint',
				'file_name' => 'MySuccessClass.php',
				'file_line' => 21,
				'issue'     =>
					array(
						'message'  => '2 syntax error, unexpected end of file',
						'level'    => 'ERROR',
						'severity' => 5,
					),
			),
		);
		$statsExpected2x2       = array(
			'error'   => 4,
			'warning' => 0,
			'info'    => 0
		);
		$fileScanningResults2x2 = array(
			15 =>
				array(
					0 =>
						array(
							'message'  => '1 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
					1 =>
						array(
							'message'  => '2 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),
			21 =>
				array(
					0 =>
						array(
							'message'  => '1 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
					1 =>
						array(
							'message'  => '2 syntax error, unexpected end of file',
							'level'    => 'ERROR',
							'severity' => 5,
						),
				),

		);

		return [
			// File with single issue in line
			// PR number, file name, scanning results, expected_submit, expected_stats
			[ 30, 'MySuccessClass.php', $fileScanningResults, $submitExpected, $statsExpected ],
			// File with 1 line with issues && 2 issues in the same line
			[ 30, 'MySuccessClass.php', $fileScanningResults1x2, $submitExpected1x2, $statsExpected1x2 ],
			// File with 2 issues in different lines
			[ 30, 'MySuccessClass.php', $fileScanningResults2x1, $submitExpected2x1, $statsExpected2x1 ],
			// File with 2 lines with 2 issues each line
			[ 30, 'MySuccessClass.php', $fileScanningResults2x2, $submitExpected2x2, $statsExpected2x2 ],
		];
	}

	/**
	 * @return \int[][]
	 */
	public function getStatsMock(): array {
		return array(
			30 =>
				array(
					'error'   => 0,
					'warning' => 0,
					'info'    => 0,
				),
			28 =>
				array(
					'error'   => 0,
					'warning' => 0,
					'info'    => 0,
				),
		);
	}

	/**
	 * @return array[]
	 */
	public function getSubmitMock(): array {
		return array(
			30 => array(),
			28 => array(),
		);
	}
}
