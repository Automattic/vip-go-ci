<?php
/**
 * Test vipgoci_ap_svg_files() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class ApSvgFilesTest extends TestCase {
	/**
	 * Git options.
	 *
	 * @var $options_git
	 */
	private array $options_git = array(
		'repo-owner'      => null,
		'repo-name'       => null,
		'github-repo-url' => null,
		'git-path'        => null,
	);

	/**
	 * SVG scan options.
	 *
	 * @var $options_svg_scan
	 */
	private array $options_svg_scan = array(
		'svg-php-path'     => null,
		'svg-scanner-path' => null,
	);

	/**
	 * Options for auto approvals.
	 *
	 * @var $options_auto_approvals
	 */
	private array $options_auto_approvals = array(
		'autoapprove-filetypes'    => null,
		'commit-test-svg-files-1'  => null,
		'commit-test-svg-files-2b' => null,
	);

	/**
	 * Setup function. Require files, initialize variables, etc
	 *
	 * @return void
	 */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';

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

		$this->options['github-token'] =
			vipgoci_unittests_get_config_value(
				'git-secrets',
				'github-token',
				true // Fetch from secrets file.
			);

		if ( empty( $this->options['github-token'] ) ) {
			$this->options['github-token'] = '';
		}

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

		$this->options['skip-draft-prs'] = false;

		$this->options['skip-large-files'] = false;

		$this->options['skip-large-files-limit'] = 15;

		$this->options['lint-modified-files-only'] = false;
	}

	/**
	 * Tear down function. Remove local git repository, clear variables.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		if (
			( ! empty( $this->options['local-git-repo'] ) ) &&
			( false !== $this->options['local-git-repo'] )
		) {
			vipgoci_unittests_remove_git_repo(
				$this->options['local-git-repo']
			);
		}

		unset( $this->options );
		unset( $this->options_auto_approvals );
		unset( $this->options_svg_scan );
		unset( $this->options_git );
	}

	/**
	 * Test common usage of the function.
	 *
	 * @covers ::vipgoci_ap_svg_files
	 */
	public function testApSvgFiles1() :void {
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

		$this->assertSame(
			array(
				'auto-approvable-1.svg' => 'ap-svg-files',
				'auto-approvable-2.svg' => 'ap-svg-files',
			),
			$auto_approved_files_arr
		);

		unset( $this->options['svg-checks'] );
	}

	/**
	 * Test auto-approvals of SVG files that
	 * have been renamed, removed, or had their
	 * permissions changed.
	 *
	 * @covers ::vipgoci_ap_svg_files
	 */
	public function testApSvgFiles2() :void {
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
			$this->options['commit-test-svg-files-2b'];

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

		$this->assertSame(
			array(
				'auto-approvable-1.svg'         => 'ap-svg-files',
				'auto-approvable-2-renamed.svg' => 'ap-svg-files',
				'auto-approvable-7.svg'         => 'ap-svg-files',
				'auto-approvable3.svg'          => 'ap-svg-files',
			),
			$auto_approved_files_arr
		);

		unset( $this->options['svg-checks'] );
	}
}
