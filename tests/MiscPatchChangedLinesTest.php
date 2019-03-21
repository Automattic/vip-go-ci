<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscPatchChangedLinesTest extends TestCase {
	var $github_config = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
		'pr-base-sha'	=> null,
		'commit-id'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'patch-changed-lines',
			$this->github_config
		);

		$this->github_config[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file
			);
	}

	/**
	 * @covers ::vipgoci_patch_changed_lines
	 */
	public function testPatchChangedLines1() {
		$options_test = vipgoci_unittests_options_test(
			$this->github_config,
			array( 'github-token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		if ( empty( $this->github_config ) ) {
			$this->markTestSkipped(
				'Must set up vipgoci_patch_changed_lines() test'
			);

			return;
		}

		vipgoci_unittests_output_suppress();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->github_config['repo-owner'],
			$this->github_config['repo-name'],
			$this->github_config['github-token'],
			$this->github_config['pr-base-sha'],
			$this->github_config['commit-id'],
			'ap-hashes-api.php'
		);

		vipgoci_unittests_output_unsuppress();

		$this->assertEquals(
			json_decode(
				'[null,"194",195,196,null,197,198,199,200,201,202]',
				true
			),
			$patch_arr
		);
	}
}
