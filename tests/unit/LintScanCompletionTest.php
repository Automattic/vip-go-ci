<?php
/**
 * Lint command failures with zero findings must retain failure evidence.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Exercise real lint commands and the production output parser. */
final class LintScanCompletionTest extends TestCase {
	/**
	 * Lint commands and expected incomplete files.
	 *
	 * @return array Command cases.
	 */
	public static function commands(): array {
		return array(
			'success'          => array( PHP_BINARY, array() ),
			'failed command'   => array( '/bin/false', array( 'file.php' ) ),
			'empty output'     => array( '/bin/true', array( 'file.php' ) ),
			'malformed output' => array( '/bin/echo', array( 'file.php' ) ),
		);
	}

	/**
	 * Distinguish clean output from execution and parsing failures.
	 *
	 * @param string $command Executable path.
	 * @param array  $expected_failures Incomplete files.
	 */
	#[DataProvider( 'commands' )]
	public function testExecutionEvidenceIsIndependentOfEmptyFindings( string $command, array $expected_failures ): void {
		foreach ( array( 'defines.php', 'lint-scan.php', 'misc.php', 'log.php', 'statistics.php', 'output-security.php' ) as $file ) {
			require_once __DIR__ . '/../../' . $file;
		}
		require_once __DIR__ . '/../integration/helper/LintScanMultipleFiles.php';
		$GLOBALS['vipgoci_debug_level'] = -1;
		$options                        = array(
			'repo-owner'             => 'owner',
			'repo-name'              => 'repo',
			'token'                  => 'token',
			'commit'                 => 'head',
			'local-git-repo'         => '',
			'skip-large-files'       => false,
			'lint-php-versions'      => array( 'test' ),
			'lint-php-version-paths' => array( 'test' => $command ),
		);
		$skipped                        = array();
		$issues                         = vipgoci_lint_scan_multiple_files( $options, array(), $skipped, array( 'file.php' ), $failed );
		$this->assertSame( array(), $issues );
		$this->assertSame( $expected_failures, $failed );
	}
}
