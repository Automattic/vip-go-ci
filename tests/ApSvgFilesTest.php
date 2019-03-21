<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApSvgFilesTest extends TestCase {
	var $options_git = array(
		'repo-owner'			=> null,
		'repo-name'			=> null,
		'github-repo-url'		=> null,
		'git-path'			=> null,
	);

	var $options_svg_scan = array(
		'svg-scanner-path'		=> null,
	);

	var $options_auto_approvals = array(
		'autoapprove-filetypes'		=> null,
		'commit-test-svg-files-1'	=> null,
	);
	
	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		vipgoci_unittests_get_config_values(
			'svg-scan',
			$this->options_svg_scan
		);

		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options_auto_approvals
		);

		$this->options = array_merge(
			$this->options_git,
			$this->options_svg_scan,
			$this->options_auto_approvals
		);

		$this->options[ 'github-token' ] =
			vipgoci_unittests_get_config_value(
				'git',
				'github-token',
				true // Fetch from secrets file
			);

		$this->options['token'] =
			$this->options['github-token'];

		unset( $this->options['github-token'] );
		
		$this->options['autoapprove'] = true;
		$this->options['autoapprove-filetypes'] =
			explode(
				',',
				$this->options['autoapprove-filetypes']
			);

		$this->options['branches-ignore'] = array();
	}

	protected function tearDown() {
		// FIXME: Remove temporary git repository
		$this->options = null;
		$this->options_auto_approvals = null;
		$this->options_svg_scan = null;
		$this->options_git = null;
	}

	/**
	 * @covers ::vipgoci_ap_svg_files
	 */
	public function testApSvgFiles1() {
		$options_test = vipgoci_unittests_options_test(
			$this->options,
			array( 'github-token', 'token' ),
			$this
		);

		if ( -1 === $options_test ) {
			return;
		}

		$auto_approved_files_arr = array();

		$this->options['svg-checks'] = true;

		$this->options['commit'] =
			$this->options['commit-test-svg-files-1'];


		vipgoci_unittests_output_suppress();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
				vipgoci_unittests_output_get()
			);

			return;
		}

		vipgoci_ap_svg_files(
			$this->options,
			$auto_approved_files_arr
		);

		vipgoci_unittests_output_unsuppress();


		$this->assertEquals(
			$auto_approved_files_arr,
			array(
				'auto-approvable-1.svg' => 'ap-svg-files',
				'auto-approvable-2.svg' => 'ap-svg-files',
			)
		);

		unset( $this->options['svg-checks'] );
	}
}
