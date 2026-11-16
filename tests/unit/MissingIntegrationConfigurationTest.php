<?php
/**
 * Check integration skips when the local INI configuration is absent.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group( 'mocked-api' )]
final class MissingIntegrationConfigurationTest extends TestCase {
	public static function configurationCases(): array {
		return array(
			'absent config.ini' => array( false ),
			'empty config.ini'  => array( true ),
		);
	}

	#[DataProvider( 'configurationCases' )]
	public function testMissingConfigurationSkipsAffectedIntegrationClasses( bool $empty_ini ): void {
		if ( 'true' === getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
			$this->markTestSkipped( 'The missing-configuration fixture runs only in the offline profile.' );
		}

		$phpunit = getenv( 'VIPGOCI_PHPUNIT' );

		if ( false === $phpunit || '' === $phpunit ) {
			$phpunit = trim( (string) shell_exec( 'command -v phpunit' ) );
		}

		$this->assertNotEmpty( $phpunit, 'Set VIPGOCI_PHPUNIT to the PHPUnit executable or PHAR.' );

		$config_dir = sys_get_temp_dir() . '/vipgoci-missing-config-' . bin2hex( random_bytes( 8 ) );
		$this->assertTrue( mkdir( $config_dir ) );
		$report_path = $config_dir . '/result.xml';
		$ini_path = $config_dir . '/config.ini';

		$classes = array(
			'GitRepoBranchGetTest',
			'GitRepoRepoFetchCommittedFileTest',
			'GitRepoRepoFetchTreeTest',
			'GitRepoRepoGetHeadTest',
			'GitRepoRepoOkTest',
			'GitRepoSubmoduleGetUrlTest',
			'GitRepoSubmodulesListTest',
			'GitRepoSubmodulesSetupTest',
			'LintScanCommitTest',
			'MiscScandirGitRepoTest',
			'PhpcsScanScanCommitTest',
			'PhpcsScanSingleFileTest',
			'StatsStatsPerFileTest',
			'SvgScanScanCommitTest',
			'SvgScanScanSingleFileTest',
			'WpscanScanFindAddonDirsAlteredTest',
			'SvgScanWithScannerTest',
			'MainRunInitOptionsSvgTest',
		);

		$command = array(
			PHP_BINARY,
			$phpunit,
			'--no-configuration',
			'--bootstrap',
			__DIR__ . '/helper/MissingIntegrationConfigBootstrap.php',
			'--do-not-cache-result',
			'--fail-on-warning',
			'--fail-on-risky',
			'--colors=never',
			'--log-junit',
			$report_path,
			'--filter',
			implode( '|', $classes ),
			dirname( __DIR__ ) . '/integration',
		);

		$environment = getenv();
		$environment['VIPGOCI_MISSING_CONFIG_DIR'] = $config_dir;
		$environment['VIPGOCI_TESTING_LIVE_API'] = 'false';

		try {
			if ( $empty_ini ) {
				$this->assertSame( 0, file_put_contents( $ini_path, '' ) );
			}

			$process = proc_open(
				$command,
				array(
					0 => array( 'pipe', 'r' ),
					1 => array( 'pipe', 'w' ),
					2 => array( 'pipe', 'w' ),
				),
				$pipes,
				dirname( __DIR__, 2 ),
				$environment
			);
			$this->assertIsResource( $process );
			fclose( $pipes[0] );
			$output = stream_get_contents( $pipes[1] );
			$error = stream_get_contents( $pipes[2] );
			fclose( $pipes[1] );
			fclose( $pipes[2] );
			$status = proc_close( $process );

			$this->assertSame( 0, $status, $output . $error );
			$this->assertFileExists( $report_path );

			$report = new DOMDocument();
			$this->assertTrue( $report->load( $report_path ) );
			$summary = $report->getElementsByTagName( 'testsuite' )->item( 0 );
			$this->assertNotNull( $summary );
			$this->assertGreaterThanOrEqual( 18, (int) $summary->getAttribute( 'skipped' ) );
			$this->assertSame( 0, (int) $summary->getAttribute( 'errors' ) );
			$this->assertSame( 0, (int) $summary->getAttribute( 'failures' ) );
			$seen_classes = array();
			$skipped_classes = array();

			foreach ( $report->getElementsByTagName( 'testcase' ) as $testcase ) {
				$class = substr( strrchr( $testcase->getAttribute( 'class' ), '\\' ), 1 );
				$seen_classes[] = $class;

				if ( 0 < $testcase->getElementsByTagName( 'skipped' )->length ) {
					$skipped_classes[] = $class;
				}
			}

			$this->assertSame( array(), array_diff( $classes, array_unique( $seen_classes ) ) );
			$this->assertSame( array(), array_diff( $classes, array_unique( $skipped_classes ) ) );
		} finally {
			if ( is_file( $report_path ) ) {
				unlink( $report_path );
			}
			if ( is_file( $ini_path ) ) {
				unlink( $ini_path );
			}
			rmdir( $config_dir );
		}
	}
}
