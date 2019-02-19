<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class ApFileTypesTest extends TestCase {
	var $options = array(
		'repo-owner'		=> null,
		'repo-name'		=> null,
		'github-token'		=> null,
		'commit'		=> null,
		'autoapprove-filetypes'	=> null,
	);

	protected function setUp() {
		vipgoci_unittests_get_config_values(
			'auto-approvals',
			$this->options
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
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_ap_file_types
	 */
	public function testFileTypes1() {
		$auto_approved_files_arr = array();

		ob_start();

		vipgoci_ap_file_types(
			$this->options,
			$auto_approved_files_arr
		);

		ob_end_clean();

		$this->assertEquals(
			$auto_approved_files_arr,
			array(
				'auto-approvable-1.txt' => 'autoapprove-filetypes',
				'auto-approvable-2.txt' => 'autoapprove-filetypes',
				'auto-approvable-3.jpg' => 'autoapprove-filetypes',
			)
		);
	}
}
