<?php
/**
 * Test vipgoci_results_output_dump() function.
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
final class ResultsOutputDumpTest extends TestCase {
	/**
	 * Temporary file to dump results to.
	 *
	 * @var $temp_dump_file
	 */
	private string $temp_dump_file = '';

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/../../results.php';
		require_once __DIR__ . '/../../log.php';

		$this->temp_dump_file = tempnam(
			sys_get_temp_dir(),
			'vipgoci-results-dump-'
		);
	}

	/**
	 * Tear down function.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unlink( $this->temp_dump_file );
		unset( $this->temp_dump_file );
	}

	/**
	 * Test if function dumps results in a file.
	 *
	 * @covers ::vipgoci_results_output_dump
	 *
	 * @return void
	 */
	public function testDumpResults(): void {
		$data = array(
			'results'        => array( 1, 2, 3, 4 ),
			'repo-owner'     => 'test-owner',
			'repo-name'      => 'test-repo',
			'commit'         => 'abc123',
			'prs_implicated' => array(
				1 => (object) array(
					'title' => 'testing #1',
					'base'  => (object) array(
						'ref' => 'main',
					),
					'head'  => (object) array(
						'ref' => 'add/testing1',
					),
					'user'  => (object) array(
						'login' => 'user1',
					),
				),
				2 => (object) array(
					'title' => 'testing #2',
					'base'  => (object) array(
						'ref' => 'main',
					),
					'head'  => (object) array(
						'ref' => 'add/testing2',
					),
					'user'  => (object) array(
						'login' => 'user2',
					),
				),
				3 => (object) array(
					'invalid' => false,
				),
			),
		);

		vipgoci_results_output_dump(
			$this->temp_dump_file,
			$data
		);

		$json_content = file_get_contents(
			$this->temp_dump_file
		);

		$dumped_contents = json_decode(
			$json_content,
			true
		);

		$this->assertSame(
			array(
				'results'        => array( 1, 2, 3, 4 ),
				'repo-owner'     => 'test-owner',
				'repo-name'      => 'test-repo',
				'commit'         => 'abc123',
				'prs_implicated' => array(
					1 => array(
						'title'       => 'testing #1',
						'base_branch' => 'main',
						'head_branch' => 'add/testing1',
						'creator'     => 'user1',
					),
					2 => array(
						'title'       => 'testing #2',
						'base_branch' => 'main',
						'head_branch' => 'add/testing2',
						'creator'     => 'user2',
					),
				),
			),
			$dumped_contents
		);
	}
}
