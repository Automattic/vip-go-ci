<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class SvgScanScanSingleFileTest extends TestCase {
	var $options_git = array(
		'git-path'		=> null,
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
		'github-token'		=> null,
	);

	var $options_svg_scan = array(
		'svg-scanner-path'				=> null,
		'commit-test-svg-scan-single-file-test-1'	=> null,
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

		$this->options = array_merge(
			$this->options_git,
			$this->options_svg_scan
		);

		$this->options['token'] =
			$this->options['github-token'];

		$this->options['svg-checks'] = true;
	}

	protected function tearDown() {
		$this->options_git = null;
		$this->options_svg_scan = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_svg_scan_single_file
	 */
	public function testSvgScanSingleFileTest1() {
		$this->options['commit'] =
			$this->options['commit-test-svg-scan-single-file-test-1'];

		ob_start();

		$this->options['local-git-repo'] =
			vipgoci_unittests_setup_git_repo(
				$this->options
			);

		if ( false === $this->options['local-git-repo'] ) {
			$this->markTestSkipped(
				'Could not set up git repository: ' .
					ob_get_flush()
			);

			return;
		}

		$ret = vipgoci_svg_scan_single_file(
			$this->options,
			'svg-file-with-issues-1.svg'
		);

		ob_end_clean();

		$temp_file_name = $ret['temp_file_name'];

		$expected_result = array(
			'file_issues_arr_master' => array(
				'totals'	=> array(
					'errors'	=> 2,
					'warnings'	=> 0,
					'fixable'	=> 0,
				),

				'files'		=> array(
					$temp_file_name	=> array(
						'errors'	=> 2,
						'messages'	=> array(
							array(
								'message'	=> "Suspicious attribute 'someotherfield2'",
								'line'		=> 8,
								'severity'	=> 5,
								'type'		=> 'ERROR',
								'source'	=> 'WordPressVIPMinimum.Security.SVG.DisallowedTags',
								'level'		=> 'ERROR',
								'fixable'	=> false,
								'column'	=> 0
							),
							array(
								'message'	=> "Suspicious attribute 'myotherfield'",
								'line'		=> 5,
								'severity'	=> 5,
								'type'		=> 'ERROR',
								'source'	=> 'WordPressVIPMinimum.Security.SVG.DisallowedTags',
								'level'		=> 'ERROR',
								'fixable'	=> false,
								'column'	=> 0
							)
						)
					)
				),
			),
			
			'temp_file_name'	=> $temp_file_name,
		);

		$expected_result['file_issues_str'] = json_encode(
			$expected_result['file_issues_arr_master']
		);
	
		$this->assertEquals(
			$ret,
			$expected_result
		);
	}
}
