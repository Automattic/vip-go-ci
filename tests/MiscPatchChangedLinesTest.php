<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscPatchChangedLinesTest extends TestCase {
	var $github_config = array(
		'repo-owner'	=> null,
		'repo-name'	=> null,
		'github-token'	=> null,
		'pr-base-sha'	=> null,
		'commit-id'	=> null,
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

			if ( $config_key === 'github-token' ) {
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
			$this->github_config['github-token']
		) ) {
			$this->github_config['github-token'] = null;
		}

		ob_start();

		$patch_arr = vipgoci_patch_changed_lines(
			$this->github_config['repo-owner'],
			$this->github_config['repo-name'],
			$this->github_config['github-token'],
			$this->github_config['pr-base-sha'],
			$this->github_config['commit-id'],
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
