<?php

namespace Vipgoci\tests;

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunEnvOptionsHandleTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
			'env-options' => 'repo-owner=REPO_OWNER,repo-name=REPO_NAME',
			'repo-owner'  => null,
			'repo-name'   => null,
		);

		$this->options_recognized = array(
			'repo-owner:',
			'repo-name:',
		);

		putenv('REPO_OWNER=myorg2');
		putenv('REPO_NAME=myrepo1');

	}

	protected function tearDown() :void {
		unset( $this->options );
		unset( $this->options_recognized );

		putenv('REPO_OWNER=');
		putenv('REPO_NAME=');
	}

	/**
	 * @covers ::vipgoci_run_env_options_handle
	 */
	public function testRunEnvOptionsHandle() {
		vipgoci_unittests_output_suppress();

		vipgoci_run_env_options_handle(
			$this->options,
			$this->options_recognized
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			array(
				'env-options' => array(
					'repo-owner=REPO_OWNER',
					'repo-name=REPO_NAME',
				),

				'repo-owner' => 'myorg2',
				'repo-name'  => 'myrepo1',
			),
			$this->options
		);
	}
}
