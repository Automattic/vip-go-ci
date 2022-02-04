<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/../../main.php' );
require_once( __DIR__ . '/../../options.php' );
require_once( __DIR__ . '/../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class MainRunInitOptionsRepoMetaApiTest extends TestCase {
	protected function setUp() :void {
		$this->options = array(
		);
	}

	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * @covers ::vipgoci_run_init_options_repo_meta_api
	 */
	public function testRunInitOptionsRepoMetaApiDefault() :void {
		vipgoci_run_init_options_repo_meta_api(
			$this->options
		);

		$this->assertSame(
			array(
				'repo-meta-api-base-url'     => null,
				'repo-meta-api-user-id'      => null,
				'repo-meta-api-access-token' => null,
			),
			$this->options
		);
	}

	/**
	 * @covers ::vipgoci_run_init_options_repo_meta_api
	 */
	public function testRunInitOptionsRepoMetaApiCustom() :void {
		$this->options = array(
			'repo-meta-api-base-url'     => 'https://api.test.local/v1/api  ',
			'repo-meta-api-user-id'      => '3500',
			'repo-meta-api-access-token' => '   test  ',	
		);

		vipgoci_run_init_options_repo_meta_api(
			$this->options
		);

		$this->assertSame(
			array(
				'repo-meta-api-base-url'     => 'https://api.test.local/v1/api',
				'repo-meta-api-user-id'      => 3500,
				'repo-meta-api-access-token' => 'test',
			),
			$this->options
		);
	}
}
