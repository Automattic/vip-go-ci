<?php
/**
 * Test vipgoci_run_init_options_lint() function.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Check if options relating to linting
 * are correctly parsed and set.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class MainRunInitOptionsLintTest extends TestCase {
	/**
	 * Path to supposedly PHP 7.3 interpreter, will
	 * be a temporary file, not PHP interpreter.
	 *
	 * @var $php73_path
	 */
	private string $php73_path = '';

	/**
	 * Path to PHP 7.4. See comment above.
	 *
	 * @var $php74_path
	 */
	private string $php74_path = '';

	/**
	 * Path to PHP 8.1. See comment above.
	 *
	 * @var $php81_path
	 */
	private string $php81_path = '';

	/**
	 * Set up variable.
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/../../main.php';
		require_once __DIR__ . '/../../options.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/helper/MainRunInitOptionsLint.php';

		$this->options = array(
			'lint'                                   => null,
			'lint-modified-files-only'               => null,
			'lint-skip-folders-in-repo-options-file' => null,
			'lint-skip-folders'                      => '/folder1/folder2/,folder3/folder4',
			'lint-php-versions'                      => '7.4,8.1',
			'lint-php-version-paths'                 => '',
		);

		$this->php_versions_to_mock = array(
			'7.3',
			'7.4',
			'8.1',
		);

		foreach ( $this->php_versions_to_mock as $php_version ) {
			$this->php_paths[ $php_version ] = tempnam(
				sys_get_temp_dir(),
				'php' . $php_version . '_mock_file'
			);

			file_put_contents(
				$this->php_paths[ $php_version ],
				'data'
			);

			if ( ! empty(
				$this->options['lint-php-version-paths']
			) ) {
				$this->options['lint-php-version-paths'] .= ',';
			}

			$this->options['lint-php-version-paths'] .=
				$php_version .
				':' .
				$this->php_paths[ $php_version ];
		}
	}

	/**
	 * Clear variable.
	 */
	protected function tearDown() :void {
		unset( $this->options );

		foreach ( $this->php_versions_to_mock as $php_version ) {
			unlink( $this->php_paths[ $php_version ] );
		}

		unset( $this->php_paths );
	}

	/**
	 * Check if options relating to linting
	 * are correctly parsed and set.
	 *
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintDefault() :void {
		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => true,
				'lint-modified-files-only'               => true,
				'lint-skip-folders-in-repo-options-file' => false,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
				'lint-php-versions'                      => array( '7.4', '8.1' ),
				'lint-php-version-paths'                 => array(
					'7.3' => $this->php_paths['7.3'],
					'7.4' => $this->php_paths['7.4'],
					'8.1' => $this->php_paths['8.1'],
				),
				'lint-file-extensions'                   => array( 'php' ),
			),
			$this->options
		);
	}

	/**
	 * Check if options relating to linting
	 * are correctly parsed and set.
	 *
	 * @covers ::vipgoci_run_init_options_lint
	 */
	public function testRunInitOptionsLintCustom() :void {
		$this->options['lint']                                   = 'false';
		$this->options['lint-modified-files-only']               = 'false';
		$this->options['lint-file-extensions']                   = 'php,inc';
		$this->options['lint-skip-folders-in-repo-options-file'] = 'true';

		vipgoci_run_init_options_lint(
			$this->options
		);

		$this->assertSame(
			array(
				'lint'                                   => false,
				'lint-modified-files-only'               => false,
				'lint-skip-folders-in-repo-options-file' => true,
				'lint-skip-folders'                      => array( 'folder1/folder2', 'folder3/folder4' ),
				'lint-php-versions'                      => null,
				'lint-php-version-paths'                 => null,
				'lint-file-extensions'                   => array( 'php', 'inc' ),
			),
			$this->options
		);
	}
}
