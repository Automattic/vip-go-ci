<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once __DIR__ . '/../../main.php';
require_once __DIR__ . '/../../options.php';
require_once __DIR__ . '/../../misc.php';

use PHPUnit\Framework\TestCase;

/**
 * Test if options relating to posting
 * generic comments are correctly set and parsed.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsPostGenericPrSupportCommentsTest extends TestCase {
	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		$this->options = array(
			'post-generic-pr-support-comments'           => null,
			'post-generic-pr-support-comments-on-drafts' => '1:false|||2:true',
			'post-generic-pr-support-comments-string'    => '1:Text1|||2:Text2',
			'post-generic-pr-support-comments-skip-if-label-exists' => '1:Label1|||2:Label2',
			'post-generic-pr-support-comments-branches'  => '1:master,orgname:master|||2:develop,orgname:develop',
			'post-generic-pr-support-comments-repo-meta-match' => '1:option1=value1,option2=value2|||2:option2=value2',
		);
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );
	}

	/**
	 * Test if default options are correct.
	 *
	 * @covers ::vipgoci_run_init_options_post_generic_pr_support_comments
	 */
	public function testRunInitOptionsPostGenericPrSupportCommentsDefault() :void {
		vipgoci_run_init_options_post_generic_pr_support_comments(
			$this->options
		);

		$this->assertSame(
			array(
				'post-generic-pr-support-comments'        => false,
				'post-generic-pr-support-comments-on-drafts' => array(
					1 => false,
					2 => true,
				),
				'post-generic-pr-support-comments-string' => array(
					1 => 'Text1',
					2 => 'Text2',
				),
				'post-generic-pr-support-comments-skip-if-label-exists' => array(
					1 => 'Label1',
					2 => 'Label2',
				),
				'post-generic-pr-support-comments-branches' => array(
					1 => array( 'master', 'orgname:master' ),
					2 => array( 'develop', 'orgname:develop' ),
				),
				'post-generic-pr-support-comments-repo-meta-match' => array(
					1 => array(
						'option1' => array( 'value1' ),
						'option2' => array( 'value2' ),
					),
					2 => array(
						'option2' => array( 'value2' ),
					),
				),
			),
			$this->options
		);
	}

	/**
	 * Check if custom options are correctly parsed.
	 *
	 * @covers ::vipgoci_run_init_options_post_generic_pr_support_comments
	 */
	public function testRunInitOptionsPostGenericPrSupportCommentsCustom() :void {
		$this->options['post-generic-pr-support-comments'] = 'true';

		vipgoci_run_init_options_post_generic_pr_support_comments(
			$this->options
		);

		$this->assertSame(
			array(
				'post-generic-pr-support-comments'        => true,
				'post-generic-pr-support-comments-on-drafts' => array(
					1 => false,
					2 => true,
				),
				'post-generic-pr-support-comments-string' => array(
					1 => 'Text1',
					2 => 'Text2',
				),
				'post-generic-pr-support-comments-skip-if-label-exists' => array(
					1 => 'Label1',
					2 => 'Label2',
				),
				'post-generic-pr-support-comments-branches' => array(
					1 => array( 'master', 'orgname:master' ),
					2 => array( 'develop', 'orgname:develop' ),
				),
				'post-generic-pr-support-comments-repo-meta-match' => array(
					1 => array(
						'option1' => array( 'value1' ),
						'option2' => array( 'value2' ),
					),
					2 => array(
						'option2' => array( 'value2' ),
					),
				),
			),
			$this->options
		);
	}
}
