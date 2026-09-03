<?php
/**
 * Compare batch and singleton findings using real PHPCS on synthetic source.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Integration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
final class PhpcsScanBatchIsolationTest extends TestCase {
	/** @var string|null Owned synthetic repository and ruleset directory. */
	private ?string $directory = null;

	/** @var array Scanner options. */
	private array $options;

	/** Use the integration suite's configured scanner; never access GitHub. */
	protected function setUp(): void {
		require_once __DIR__ . '/IncludesForTests.php';
		$path = vipgoci_unittests_get_config_value( 'phpcs-scan', 'phpcs-path' );
		$php  = vipgoci_unittests_get_config_value( 'phpcs-scan', 'phpcs-php-path' );
		if ( empty( $path ) || empty( $php ) || ! is_file( $path ) || ! is_file( $php ) ) {
			$this->markTestSkipped( 'Configure phpcs-path and phpcs-php-path in tests/config.ini.' );
		}
		$GLOBALS['vipgoci_debug_level'] = -1;
		$this->directory                = tempnam( sys_get_temp_dir(), 'vipgoci-batch-parity-' );
		unlink( $this->directory );
		mkdir( $this->directory );
		$this->git( 'init -q' );
		$standard = $this->directory . '/standard.xml';
		vipgoci_phpcs_write_xml_standard_file( $standard, array(), array( 'Generic.Files.LineLength' ) );
		$this->options = array(
			'repo-owner'           => 'fixture',
			'repo-name'            => 'fixture',
			'token'                => 'unused',
			'local-git-repo'       => $this->directory,
			'phpcs-path'           => $path,
			'phpcs-php-path'       => $php,
			'phpcs-standard'       => array( $standard ),
			'phpcs-sniffs-exclude' => array(),
			'phpcs-severity'       => 1,
			'phpcs-runtime-set'    => array(),
			'skip-large-files'     => false,
			'svg-checks'           => false,
			'svg-file-extensions'  => array( 'svg' ),
		);
	}

	/** Remove only files inside this test's temporary directory. */
	protected function tearDown(): void {
		if ( null === $this->directory ) {
			return;
		}
		$entries = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $this->directory, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $entries as $entry ) {
			if ( $entry->isDir() && ! $entry->isLink() ) {
				rmdir( $entry->getPathname() );
			} else {
				unlink( $entry->getPathname() );
			}
		}
		rmdir( $this->directory );
	}

	/**
	 * @param string $arguments Trusted fixture setup arguments.
	 * @return string Git output.
	 */
	private function git( string $arguments ): string {
		exec(
			'git -c user.name=Fixture -c user.email=fixture@example.invalid -c commit.gpgsign=false -c core.hooksPath=/dev/null -C ' .
				escapeshellarg( $this->directory ) . ' ' . $arguments . ' 2>&1',
			$output,
			$code
		);
		$this->assertSame( 0, $code, implode( "\n", $output ) );
		return implode( "\n", $output );
	}

	/** @return array Modern/legacy settings, ignores and per-file suppression. */
	public static function annotations(): array {
		$long_line = '// ' . str_repeat( 'long ', 33 ) . "\n";
		return array(
			'settings'           => array( "// phpcs:set Generic.Files.LineLength lineLimit 1000\n// phpcs:set Generic.Files.LineLength absoluteLineLimit 1000\n" . $long_line, 1 ),
			'legacy settings'    => array( "// @codingStandardsChangeSetting Generic.Files.LineLength lineLimit 1000\n// @codingStandardsChangeSetting Generic.Files.LineLength absoluteLineLimit 1000\n" . $long_line, 1 ),
			'ignore first line'  => array( "<?php // phpcs:ignoreFile\n" . $long_line, 0 ),
			'ignore second line' => array( "// PHPCS:IGNOREFILE\n" . $long_line, 0 ),
			'legacy ignore'      => array( "// @codingStandardsIgnoreFile\n" . $long_line, 0 ),
			'disable'            => array( "// phpcs:disable Generic.Files.LineLength\n" . $long_line, 0 ),
			'ignore line'        => array( "// phpcs:ignore Generic.Files.LineLength\n" . $long_line, 0 ),
		);
	}

	/**
	 * Every ordinary neighbour must retain its error regardless of prior annotations.
	 *
	 * @param string $annotated Annotated source, with or without an opening tag.
	 * @param int    $errors    Expected errors in the annotated file itself.
	 */
	#[DataProvider( 'annotations' )]
	public function testBatchFindingsMatchSingletons( string $annotated, int $errors ): void {
		file_put_contents( $this->directory . '/a.php', str_starts_with( $annotated, '<?php' ) ? $annotated : "<?php\n" . $annotated );
		file_put_contents( $this->directory . '/b.php', "<?php\n// " . str_repeat( 'long ', 33 ) . "\n" );
		file_put_contents( $this->directory . '/c.php', "<?php\n// clean\n" );
		$this->git( 'add -A' );
		$this->git( 'commit -qm fixture' );
		$this->options['commit'] = $this->git( 'rev-parse HEAD' );
		$expected                = array();
		foreach ( array( 'a.php', 'b.php', 'c.php' ) as $name ) {
			$result = vipgoci_phpcs_scan_single_file( $this->options, $name );
			$this->assertNotNull( $result['file_issues_arr_master'] );
			$expected[ $name ] = $result['file_issues_arr_master']['files'][ $result['temp_file_name'] ];
			$this->assertFileDoesNotExist( $result['temp_file_name'] );
		}
		// LineLength scans the file at its opening tag, before setting directives apply.
		$this->assertSame( $errors, $expected['a.php']['errors'] );
		$this->assertSame( 1, $expected['b.php']['errors'] );
		$actual = array();
		foreach ( vipgoci_phpcs_scan_files( $this->options, array_keys( $expected ) ) as $name => $result ) {
			$this->assertNotNull( $result['file_issues_arr_master'] );
			$actual[ $name ] = $result['file_issues_arr_master']['files'][ $result['temp_file_name'] ];
			$this->assertFileDoesNotExist( $result['temp_file_name'] );
		}
		$this->assertSame( $expected, $actual );
	}
}
