<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscPatchChangedLinesTest extends TestCase {
	var $github_config = array(
		'repo_owner'	=> null,
		'repo_name'	=> null,
		'github_token'	=> null,
		'pr_base_sha'	=> null,
		'commit_id'	=> null,
	);

	protected function setUp() {
		foreach (
			array_keys( $this->github_config ) as $config_key
		) {
			$this->github_config[ $config_key ] =
				vipgoci_unittests_get_config_value(
					'patch-changed-lines',
					$config_key
				);

			if ( $config_key === 'github_token' ) {
				continue;
			}

			if ( empty( $this->github_config[ $config_key ] ) ) {
				$this->github_config = null;
				break;
			}
		}
	}

	/**
	 * @covers ::vipgoci_patch_changed_lines
	 */
	public function testPatchChangedLines1() {
		if ( empty( $this->github_config ) ) {
			$this->markTestSkipped(
				'Must set up vipgoci_patch_changed_lines() test'
			);

			return;
		}

		if ( empty(
			$this->github_config['github_token']
		) ) {
			$this->github_config['github_token'] = null;
		}

		ob_start();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->github_config['repo_owner'],
			$this->github_config['repo_name'],
			$this->github_config['github_token'],
			$this->github_config['pr_base_sha'],
			$this->github_config['commit_id'],
			'ap-hashes-api.php'
		);

		ob_end_clean();

		$this->assertEquals(
			json_decode(
				'[null,"194",195,196,null,197,198,199,200,201,202]',
				true
			),
			$patch_arr
		);
	}
}
