<?php
/**
 * Ensure all test files end with 'Test.php', unless exempt.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Check if all tests are correctly named.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class AllIntegrationTestsInplaceTest extends TestCase {
	/**
	 * Check all tests.
	 */
	public function testAllUnitTestsInPlace() {
		$files_arr = scandir( 'tests/integration' );

		/*
		 * Filter away any files that
		 * should be in the tests/ directory,
		 * but should not be tested -- they
		 * are support files, etc. Also
		 * filter away files that will be
		 * tested, based on their names (end
		 * with "Test.php").
		 */
		$files_arr = array_filter(
			$files_arr,
			function( $file_item ) {
				switch ( $file_item ) {
					case '.':
					case '..':
					case 'helper':
					case 'helper-files':
					case 'helper-scripts':
					case 'Skeleton.php':
					case 'IncludesForTests.php':
					case 'IncludesForTestsConfig.php':
					case 'IncludesForTestsOutputControl.php':
					case 'IncludesForTestsMisc.php':
					case 'IncludesForTestsRepo.php':
					case 'GitDiffsFetchUnfilteredTrait.php':
						/*
						 * Remove those away from
						 * the resulting array, are
						 * supporting files.
						 */
						return false;
				}

				$file_item_end = strpos(
					$file_item,
					'Test.php'
				);

				if ( false !== $file_item_end ) {
					/*
					 * If the filename ends with 'Test.php',
					 * skip this file from the final result.
					 */
					return false;
				}

				/*
				 * Any other files,
				 * keep them in.
				 */
				return true;
			}
		);

		/*
		 * We should end with an empty array.
		 */
		$this->assertSame(
			0,
			count( $files_arr )
		);
	}
}
