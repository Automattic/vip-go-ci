<?php
/**
 * Twig warning normalization must not weaken raw scanner report validation.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../phpcs-scan.php';

/** Validate Twig normalization independently of scanner execution. */
final class PhpcsTwigReportTest extends TestCase {
	/** Accidental mixed direct calls must fail rather than silently override mappings. */
	public function testDirectMixedScanIsRejectedBeforeExecution(): void {
		$this->assertNull( vipgoci_phpcs_do_scan( array( '/a.twig', '/b.php' ), 'unused', 'unused', array(), array(), 1, array() ) );
	}

	/**
	 * Complete raw report with the expected tokenizer warning.
	 *
	 * @param string $filename Requested path.
	 * @param string $type     Scanner message type.
	 * @return array Raw scanner report.
	 */
	private function report( string $filename, string $type = 'WARNING' ): array {
		$totals = array(
			'errors'   => 'ERROR' === $type ? 1 : 0,
			'warnings' => 'WARNING' === $type ? 1 : 0,
			'fixable'  => 0,
		);
		return array(
			'totals' => $totals,
			'files'  => array(
				$filename => array(
					'errors'   => $totals['errors'],
					'warnings' => $totals['warnings'],
					'messages' => array(
						array(
							'message'  => 'No PHP code found.',
							'source'   => 'Internal.NoCodeFound',
							'type'     => $type,
							'line'     => 1,
							'column'   => 1,
							'severity' => 5,
							'fixable'  => false,
						),
					),
				),
			),
		);
	}

	/** Suppression is limited to Twig warnings, including uppercase extensions. */
	public function testOnlyTwigNoCodeWarningsAreRemoved(): void {
		foreach ( array( '/a.twig', '/b.TWIG', '/c.php', '/d.js' ) as $name ) {
			$result = vipgoci_phpcs_parse_report( json_encode( $this->report( $name ) ), array( $name ) );
			$count  = in_array( $name, array( '/a.twig', '/b.TWIG' ), true ) ? 0 : 1;
			$this->assertSame( $count, $result[ $name ]['totals']['warnings'] );
			$this->assertSame( $count, $result[ $name ]['files'][ $name ]['warnings'] );
			$this->assertCount( $count, $result[ $name ]['files'][ $name ]['messages'] );
		}
		$result = vipgoci_phpcs_parse_report( json_encode( $this->report( '/a.twig', 'ERROR' ) ), array( '/a.twig' ) );
		$this->assertSame( 1, $result['/a.twig']['totals']['errors'] );
	}

	/** Counts are checked before expected warnings are removed. */
	public function testMalformedTwigReportsRemainFailures(): void {
		$report                      = $this->report( '/a.twig' );
		$report['totals']['warnings'] = 0;
		$this->assertNull( vipgoci_phpcs_parse_report( json_encode( $report ), array( '/a.twig' ) ) );
		$report                               = $this->report( '/a.twig' );
		$report['files']['/a.twig']['warnings'] = 0;
		$this->assertNull( vipgoci_phpcs_parse_report( json_encode( $report ), array( '/a.twig' ) ) );
		$this->assertNull( vipgoci_phpcs_parse_report( json_encode( $this->report( '/a.twig' ) ), array( '/a.twig', '/missing.twig' ) ) );
	}

	/** Other warnings, their metadata and normalized counts must remain intact. */
	public function testOtherTwigWarningsAreRetained(): void {
		$report          = $this->report( '/a.twig' );
		$other           = $report['files']['/a.twig']['messages'][0];
		$other['source']  = 'WordPressVIPMinimum.Security.Twig.RawFound';
		$other['fixable'] = true;
		$report['files']['/a.twig']['messages'][] = $other;
		$report['files']['/a.twig']['warnings']   = 2;
		$report['totals']['warnings']            = 2;
		$report['totals']['fixable']             = 1;
		$result = vipgoci_phpcs_parse_report( json_encode( $report ), array( '/a.twig' ) );
		$this->assertSame( array( $other ), $result['/a.twig']['files']['/a.twig']['messages'] );
		$this->assertSame(
			array(
				'errors'   => 0,
				'warnings' => 1,
				'fixable'  => 1,
			),
			$result['/a.twig']['totals']
		);
	}
}
