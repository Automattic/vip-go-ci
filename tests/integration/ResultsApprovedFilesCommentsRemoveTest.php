<?php
/**
 * Test vipgoci_results_approved_files_comments_remove().
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ResultsApprovedFilesCommentsRemoveTest extends TestCase {
	/**
	 * Setup function. Require file.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/IncludesForTests.php';
	}

	/**
	 * Results set for test.
	 *
	 * @var $results_arr
	 */
	private array $results_arr = array(
		'issues' => array(
			'32' => array(
				array(
					'type'      => 'phpcs',
					'file_name' => 'php-file-1.php',
					'file_line' => 7,
					'issue'     => array(
						'message'  => 'json_encode() is discouraged. Use wp_json_encode() instead.',
						'source'   => 'WordPress.WP.AlternativeFunctions.json_encode_json_encode',
						'severity' => 5,
						'fixable'  => false,
						'line'     => 7,
						'column'   => 6,
						'level'    => 'WARNING',
					),
				),
				array(
					'type'      => 'phpcs',
					'file_name' => 'php-file-2.php',
					'file_line' => 3,
					'issue'     => array(
						'message'  => 'All output should be run through an escaping function (see the Security sections in the WordPress Developer Handbooks), found \'mysql_query\'.',
						'source'   => 'WordPress.Security.EscapeOutput.OutputNotEscaped',
						'severity' => 5,
						'fixable'  => false,
						'line'     => 3,
						'column'   => 6,
						'level'    => 'ERROR',
					),
				),
				array(
					'type'      => 'phpcs',
					'file_name' => 'php-file-2.php',
					'file_line' => 3,
					'issue'     => array(
						'message'  => 'Extension \'mysql_\' is deprecated since PHP 5.5 and removed since PHP 7.0; Use mysqli instead',
						'source'   => 'PHPCompatibility.Extensions.RemovedExtensions.mysql_DeprecatedRemoved',
						'severity' => 5,
						'fixable'  => false,
						'line'     => 3,
						'column'   => 6,
						'level'    => 'ERROR',
					),
				),
				array(
					'type'      => 'wpscan-api',
					'file_name' => 'theme-1.css',
					'file_line' => 3,
					'issue'     => array(
						'addon_type' => 'vipgoci-addon-theme',
						'message'    => 'my-theme',
						'level'      => 'ERROR',
						'severity'   => 10,
					),
				),
				array(
					'type'      => 'wpscan-api',
					'file_name' => 'plugin-1.php',
					'file_line' => 3,
					'issue'     => array(
						'addon_type' => 'vipgoci-addon-plugin',
						'message'    => 'my-plugin',
						'level'      => 'ERROR',
						'severity'   => 10,
					),
				),


			),
		),
		'stats'  => array(
			'phpcs'      => array(
				'32' => array(
					'error'   => 2,
					'warning' => 1,
					'info'    => 0,
				),
			),
			'lint'       => array(
				'32' => array(
					'error'   => 0,
					'warning' => 0,
					'info'    => 0,
				),
			),
			'wpscan-api' => array(
				'32' => array(
					'error'   => 2,
					'warning' => 0,
					'info'    => 0,
				),
			),
		),
	);

	/**
	 * Test function and compare result with expected results.
	 *
	 * @return void
	 *
	 * @covers ::vipgoci_results_approved_files_comments_remove
	 */
	public function testRemoveCommentFromResults() :void {
		$results_altered = $this->results_arr;

		/*
		 * Set auto-approved files array. Note that values
		 * do not have any specific meaning.
		 */
		$auto_approved_files_arr = array(
			'php-file-1.php' => 'autoapprove-approved-php-file', // This file should be removed from results.
			'theme-1.css'    => 'autoapprove-filetypes', // This one not.
			'plugin-1.php'   => 'autoapprove-approved-php-file', // And not this one either.
		);

		/*
		 * Prepare expected results based on original.
		 * Manually remove the items applicable.
		 */
		$results_expected = $this->results_arr;

		unset( $results_expected['issues']['32'][0] );

		$results_expected['issues']['32'] = array_values(
			$results_expected['issues']['32']
		);

		$results_expected['stats']['phpcs']['32']['warning'] = 0;

		/*
		 * Run function with provided data.
		 */
		vipgoci_unittests_output_suppress();

		vipgoci_results_approved_files_comments_remove(
			array(),
			$results_altered,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();

		/*
		 * Check if actual and expected results are the same.
		 */
		$this->assertSame(
			$results_expected,
			$results_altered
		);
	}
}
