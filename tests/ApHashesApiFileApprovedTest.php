<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApHashesApiFileApprovedTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-token'			=> null,
		'github-repo-url'		=> null,
		'git-path'			=> null,
	);

	var $options_auto_approvals = array(
		'commit-test-ap-hashes-file-approved-1'	=> null,
	);
	
	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals
		);
		$this->options = array_merge(
			$this->options_git,
			$this->options_auto_approvals
		);

		$this->options['token'] =
			$this->options['github-token'];

		unset( $this->options['github-token'] );
		
		$this->options['branches-ignore'] = array();

		foreach (
			array(
				'hashes-api-url',
				'hashes-oauth-token',
				'hashes-oauth-token-secret',
				'hashes-oauth-consumer-key',
				'hashes-oauth-consumer-secret',
			) as $option_secret_key
		) {
			$this->options[ $option_secret_key ] =
				vipgoci_unittests_get_config_value(
					'auto-approvals-secrets',
					$option_secret_key,
					true // Fetch from secrets file
				);
		}
	
		$this->options['commit'] =
			$this->options['commit-test-ap-hashes-file-approved-1'];

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);
	}

	protected function tearDown() {
		$this->options = null;
		$this->options_auto_approvals = null;
		$this->options_git = null;
	}

	protected function validOptionsCheck() {
		foreach( array_keys(
			$this->options
		) as $option_key ) {
			if ( 'github-token' === $option_key ) {
				continue;
			}

			if ( 'token' === $option_key ) {
				continue;
			}

			if ( null === $this->options[ $option_key ] ) {
				$this->markTestskipped(
					'Missing option: ' . $option_key . ' -- cannot continue'
				);

				continue;
			}
		}
	}

	/**
	 * @covers ::vipgoci_ap_hashes_api_file_approved
	 */
	public function testApHashesApiFileApproved1() {
		$auto_approved_files_arr = array();

		$this->validOptionsCheck();

		ob_start();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				ob_get_flush()
			);

			return;
		}

		$file_status = vipgoci_ap_hashes_api_file_approved(
			$this->options,
			'approved-1.php'
		);

		ob_end_clean();

		$this->assertTrue(
			$file_status
		);
	}

	/**
	 * @covers ::vipgoci_ap_hashes_api_file_approved
	 */
	public function testApHashesApiFileApproved2() {
		$auto_approved_files_arr = array();

		$this->validOptionsCheck();

		ob_start();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				ob_get_flush()
			);

			return;
		}

		$file_status = vipgoci_ap_hashes_api_file_approved(
			$this->options,
			'not-approved-1.php'
		);

		ob_end_clean();

		$this->assertFalse(
			$file_status
		);
	}

	/**
	 * @covers ::vipgoci_ap_hashes_api_file_approved
	 */
	public function testApHashesApiFileApproved3() {
		$auto_approved_files_arr = array();

		$this->validOptionsCheck();

		ob_start();

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				ob_get_flush()
			);

			return;
		}

		// Invalid config
		$this->options['hashes-api-url'] .= "////";

		$file_status = vipgoci_ap_hashes_api_file_approved(
			$this->options,
			'not-approved-1.php'
		);

		ob_end_clean();

		$this->assertNull(
			$file_status
		);
	}
}
