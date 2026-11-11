<?php
/**
 * Approval requires positive scanner evidence, independently of finding counts.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Check per-run completion evidence at the approval boundary. */
final class ScanCompletionTest extends TestCase {
	/** Load the production evidence helpers. */
	protected function setUp(): void {
		require_once __DIR__ . '/../../defines.php';
		require_once __DIR__ . '/../../statistics.php';
		require_once __DIR__ . '/../../rebase-approval.php';
		require_once __DIR__ . '/../../wpscan-scan.php';
	}

	/**
	 * Enabled scanner options.
	 *
	 * @return array Scanner names.
	 */
	public static function scanners(): array {
		return array_map( static fn( $scanner ) => array( $scanner ), array( 'lint', 'phpcs', 'wpscan-api', 'svg-checks' ) );
	}

	/**
	 * Require literal success from each enabled scanner on this PR and head.
	 *
	 * @param string $scanner Scanner option name.
	 */
	#[DataProvider( 'scanners' )]
	public function testEveryEnabledScannerNeedsFreshPerPrSuccess( string $scanner ): void {
		$options = array(
			'commit' => 'head',
			$scanner => true,
		);
		$results = array(
			'issues'        => array( 7 => array() ),
			'stats'         => array(
				$scanner => array(
					7 => array(
						'error'   => 0,
						'warning' => 0,
						'info'    => 0,
					),
				),
			),
			'skipped-files' => array( 7 => array( 'total' => 0 ) ),
		);
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		vipgoci_scan_completion_init( $options, array( (object) array( 'number' => 7 ) ), $results );
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['scan-completion'][ $scanner ][8] = true;
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['scan-completion'][ $scanner ][7] = 'true';
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['scan-completion'][ $scanner ][7] = true;
		$results['rebase-clear']['phpcs'][7]       = true;
		$this->assertTrue( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['scan-completion']['commit'] = 'old-head';
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		vipgoci_scan_completion_init( $options, array( (object) array( 'number' => 7 ) ), $results );
		$this->assertSame( array( 7 => false ), $results['scan-completion'][ $scanner ] );
		$options[ $scanner ] = false;
		unset( $results['stats'][ $scanner ] );
		$this->assertTrue( vipgoci_rebase_scan_clear( $results, 7, $options ) );
	}

	/** Reject a removed statistics bucket even with a completion flag. */
	public function testMissingScannerStatisticsCannotAuthorizeApproval(): void {
		$options = array(
			'commit' => 'head',
			'phpcs'  => true,
		);
		$results = array(
			'issues'          => array( 7 => array() ),
			'stats'           => array(),
			'skipped-files'   => array( 7 => array( 'total' => 0 ) ),
			'scan-completion' => array(
				'commit' => 'head',
				'phpcs'  => array( 7 => true ),
			),
		);
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
	}

	/** Require coverage of every relevant changed path. */
	public function testWpscanRequiresCoverageForEveryRelevantChangedPath(): void {
		$roots = array( 'plugins', 'themes' );
		$this->assertTrue( vipgoci_wpscan_pr_scan_complete( array( 'README.md' ), array(), $roots ) );
		$this->assertFalse( vipgoci_wpscan_pr_scan_complete( array( 'plugins/a/main.php' ), array(), $roots ) );
		$this->assertFalse( vipgoci_wpscan_pr_scan_complete( array( 'plugins/a/main.php' ), array( 'plugins/a' => false ), $roots ) );
		$this->assertTrue(
			vipgoci_wpscan_pr_scan_complete(
				array( 'plugins/a/main.php' ),
				array(
					'plugins/a' => true,
					'plugins/b' => false,
				),
				$roots
			)
		);
		$this->assertFalse( vipgoci_wpscan_pr_scan_complete( array( 'plugins/a/main.php', 'themes/b/style.css' ), array( 'plugins/a' => true ), $roots ) );
	}

	/**
	 * WPScan response scenarios that yield no reported vulnerabilities.
	 *
	 * @return array Scenario and expected completion.
	 */
	public static function wpscanResponses(): array {
		return array( array( 'success', true ), array( 'transport failure', false ), array( 'malformed response', false ), array( 'missing slug', false ), array( 'missing metadata', false ) );
	}

	/**
	 * Missing API/metadata evidence must not count as a clean WPScan result.
	 *
	 * @param string $scenario Failure to inject at the external boundary.
	 * @param bool   $expected Expected completion.
	 */
	#[DataProvider( 'wpscanResponses' )]
	public function testWpscanCompletionTracksActualApiResult( string $scenario, bool $expected ): void {
		foreach ( array( 'misc.php', 'log.php', 'output-security.php', 'other-web-services.php', 'wpscan-api.php' ) as $file ) {
			require_once __DIR__ . '/../../' . $file;
		}
		require_once __DIR__ . '/helper/GitHubRequestGuards.php';
		require_once __DIR__ . '/../integration/helper/LintScanMultipleFiles.php';
		$GLOBALS['vipgoci_debug_level']         = -1;
		$GLOBALS['vipgoci_test_http_requests']  = array();
		$report                                 = array(
			'a' => array(
				'friendly_name'   => 'A',
				'latest_version'  => '1.0',
				'last_updated'    => '2026-01-01',
				'vulnerabilities' => array(),
			),
		);
		$GLOBALS['vipgoci_test_http_responses'] = array( 'GET /api/v3/plugins/a' => 'transport failure' === $scenario ? null : ( 'malformed response' === $scenario ? array() : $report ) );
		$addon                                  = array(
			'slug'             => 'missing slug' === $scenario ? '' : 'a',
			'version_detected' => '1.0',
			'type'             => VIPGOCI_ADDON_PLUGIN,
			'file_name'        => '/repo/plugins/a/main.php',
		);
		$options                                = array(
			'repo-owner'       => 'owner',
			'repo-name'        => 'repo',
			'token'            => 'token',
			'commit'           => 'head',
			'local-git-repo'   => '/repo',
			'wpscan-api-token' => 'token',
		);
		$result                                 = vipgoci_wpscan_scan_dirs_altered( $options, array( 'plugins/a' ), array( 'plugins/a' => 'missing metadata' === $scenario ? array() : array( 'addon' => $addon ) ), $completion );
		$this->assertSame( array(), $result );
		$this->assertSame( array( 'plugins/a' => $expected ), $completion );
	}
	/** An unreadable tree must not become a successfully discovered empty tree. */
	public function testFailedTreeDiscoveryIsNotSuccessfulNoWork(): void {
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../log.php';
		$GLOBALS['vipgoci_debug_level'] = -1;
		$path                           = tempnam( sys_get_temp_dir(), 'vipgoci-missing-tree-' );
		unlink( $path );
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Exercise a known missing directory.
		$this->assertSame( array(), @vipgoci_scandir_git_repo( $path, true, null, null, $complete ) );
		$this->assertFalse( $complete );
	}
	/** Successful execution and zero reported counters cannot substitute for raw evidence. */
	public function testRebaseRequiresExplicitUnsuppressedRawPhpcsEvidence(): void {
		$options = array(
			'commit' => 'head',
			'phpcs'  => true,
		);
		$results = array(
			'issues'          => array( 7 => array() ),
			'stats'           => array( 'phpcs' => array( 7 => array( 'error' => 0 ) ) ),
			'skipped-files'   => array( 7 => array( 'total' => 0 ) ),
			'scan-completion' => array(
				'commit' => 'head',
				'phpcs'  => array( 7 => true ),
			),
		);
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['rebase-clear']['phpcs'][7] = false;
		$this->assertFalse( vipgoci_rebase_scan_clear( $results, 7, $options ) );
		$results['rebase-clear']['phpcs'][7] = true;
		$this->assertTrue( vipgoci_rebase_scan_clear( $results, 7, $options ) );
	}
}
