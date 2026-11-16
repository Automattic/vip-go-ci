<?php
/**
 * Test WordPress.org addon response normalization with offline HTTP fixtures.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Vipgoci\Tests\Common\HttpApiMock;
use Vipgoci\Tests\Common\MockedApiTestCase;

require_once __DIR__ . '/../common/MockedApiTestCase.php';

/**
 * Exercise the production helpers with only the HTTP transport replaced.
 */
#[Group( 'mocked-api' )]
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState( false )]
#[CoversFunction( 'vipgoci_wpcore_api_determine_slug_and_other_for_addons' )]
#[CoversFunction( 'vipgoci_wpcore_misc_assign_addon_fields' )]
final class WpCoreApiAddonResponseNormalizationTest extends MockedApiTestCase {
	/**
	 * Load the production helper after the transport double.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		require_once __DIR__ . '/../../wp-core-misc.php';
	}

	/**
	 * A response containing two themes must assign both, as must a singleton.
	 *
	 * @return void
	 */
	public function testThemesAreNormalizedPerResult(): void {
		foreach ( array(
			array( 'themes', array( 'first-theme' ) ),
			array( 'themes', array( 'first-theme', 'second-theme' ) ),
			array( 'no_update', array( 'first-theme', 'second-theme' ) ),
		) as list( $response_array, $theme_slugs ) ) {
			$addons = array();
			$themes = array();
			foreach ( $theme_slugs as $slug ) {
				$path                                        = $slug . '/style.css';
				$addons[ VIPGOCI_ADDON_THEME . '-' . $path ] = $this->addon( VIPGOCI_ADDON_THEME, $slug );
				$themes[ $path ]                             = $this->response_item( $slug, 'theme' );
			}
			$response                    = array(
				'themes'       => array(),
				'no_update'    => array(),
				'translations' => array(),
			);
			$response[ $response_array ] = $themes;
			$this->expect_response( VIPGOCI_ADDON_THEME, $addons, $response );
			$details  = vipgoci_wpcore_api_determine_slug_and_other_for_addons( $addons );
			$assigned = vipgoci_wpcore_misc_assign_addon_fields( $addons, $details );
			$this->assertSame( array_keys( $addons ), array_keys( $assigned ) );
			foreach ( $theme_slugs as $slug ) {
				$key = VIPGOCI_ADDON_THEME . '-' . $slug . '/style.css';
				$this->assertSame( $slug, $details[ $key ]['slug'] );
				$this->assertSame( $slug, $assigned[ $key ]['slug'] );
			}
		}
	}

	/**
	 * Reject an earlier invalid plugin in either response array while keeping a later valid one.
	 *
	 * @return void
	 */
	public function testEarlierInvalidPluginIsRejectedInBothResponseArrays(): void {
		foreach ( array( 'no_update', 'plugins' ) as $response_array ) {
			$addons                      = array(
				VIPGOCI_ADDON_PLUGIN . '-first/first.php' => $this->addon( VIPGOCI_ADDON_PLUGIN, 'first' ),
				VIPGOCI_ADDON_PLUGIN . '-second/second.php' => $this->addon( VIPGOCI_ADDON_PLUGIN, 'second' ),
			);
			$response                    = array(
				'plugins'      => array(),
				'no_update'    => array(),
				'translations' => array(),
			);
			$response[ $response_array ] = array(
				'first/first.php'   => $this->response_item( 'bad/slug' ),
				'second/second.php' => $this->response_item( 'second' ),
			);
			$this->expect_response( VIPGOCI_ADDON_PLUGIN, $addons, $response );
			$details  = vipgoci_wpcore_api_determine_slug_and_other_for_addons( $addons );
			$assigned = vipgoci_wpcore_misc_assign_addon_fields( $addons, $details );
			$this->assertArrayNotHasKey( VIPGOCI_ADDON_PLUGIN . '-first/first.php', $details );
			$this->assertArrayNotHasKey( 'slug', $assigned[ VIPGOCI_ADDON_PLUGIN . '-first/first.php' ] );
			$this->assertSame( 'second', $assigned[ VIPGOCI_ADDON_PLUGIN . '-second/second.php' ]['slug'] );
		}
	}

	/**
	 * A non-string provider slug must be rejected without a TypeError.
	 *
	 * @return void
	 */
	public function testNonStringPluginSlugIsRejected(): void {
		$addons   = array(
			VIPGOCI_ADDON_PLUGIN . '-first/first.php'   => $this->addon( VIPGOCI_ADDON_PLUGIN, 'first' ),
			VIPGOCI_ADDON_PLUGIN . '-second/second.php' => $this->addon( VIPGOCI_ADDON_PLUGIN, 'second' ),
		);
		$response = array(
			'plugins'   => array(
				'first/first.php'   => $this->response_item( array( 'invalid' ) ),
				'second/second.php' => $this->response_item( 'second' ),
			),
			'no_update' => array(),
		);
		$this->expect_response( VIPGOCI_ADDON_PLUGIN, $addons, $response );
		$details = vipgoci_wpcore_api_determine_slug_and_other_for_addons( $addons );
		$this->assertArrayNotHasKey( VIPGOCI_ADDON_PLUGIN . '-first/first.php', $details );
		$this->assertSame( 'second', $details[ VIPGOCI_ADDON_PLUGIN . '-second/second.php' ]['slug'] );
	}

	/**
	 * Effective response selection must retain the existing isset precedence.
	 *
	 * @return void
	 */
	public function testDuplicatePrecedenceAndNullFallback(): void {
		$addons = array();
		foreach ( array( 'preferred', 'rejected', 'null-fallback', 'valid-control' ) as $slug ) {
			$addons[ VIPGOCI_ADDON_PLUGIN . '-' . $slug . '/' . $slug . '.php' ] = $this->addon( VIPGOCI_ADDON_PLUGIN, $slug );
		}
		$response = array(
			'no_update' => array(
				'preferred/preferred.php'         => $this->response_item( 'preferred' ),
				'rejected/rejected.php'           => $this->response_item( 'invalid/slug' ),
				'null-fallback/null-fallback.php' => null,
			),
			'plugins'   => array(
				'preferred/preferred.php'         => $this->response_item( 'wrong-preferred' ),
				'rejected/rejected.php'           => $this->response_item( 'wrong-fallback' ),
				'null-fallback/null-fallback.php' => $this->response_item( 'null-fallback' ),
				'valid-control/valid-control.php' => $this->response_item( 'valid-control' ),
			),
		);
		$this->expect_response( VIPGOCI_ADDON_PLUGIN, $addons, $response );
		$details  = vipgoci_wpcore_api_determine_slug_and_other_for_addons( $addons );
		$assigned = vipgoci_wpcore_misc_assign_addon_fields( $addons, $details );
		$this->assertArrayNotHasKey( VIPGOCI_ADDON_PLUGIN . '-rejected/rejected.php', $details );
		$this->assertSame(
			array( 'preferred', 'null-fallback', 'valid-control' ),
			array_values( array_column( $assigned, 'slug' ) )
		);
	}

	/**
	 * Make a minimal addon input for the WordPress.org helper.
	 *
	 * @param string $type Addon type.
	 * @param string $name Header name.
	 *
	 * @return array
	 */
	private function addon( string $type, string $name ): array {
		return array(
			'type'          => $type,
			'addon_headers' => array(
				'Name'    => $name,
				'Version' => '1.0',
			),
		);
	}

	/**
	 * Make a response with all fields needed for assignment.
	 *
	 * @param mixed  $slug      Provider slug.
	 * @param string $slug_field Field holding the provider slug.
	 *
	 * @return array
	 */
	private function response_item( mixed $slug, string $slug_field = 'slug' ): array {
		return array(
			$slug_field   => $slug,
			'new_version' => '2.0',
			'package'     => 'https://downloads.wordpress.org/example.zip',
			'url'         => 'https://wordpress.org/example/',
		);
	}

	/**
	 * Queue the exact request expected from the production helper.
	 *
	 * @param string $type     Addon type.
	 * @param array  $addons   Addon inputs.
	 * @param array  $response HTTP response data.
	 *
	 * @return void
	 */
	private function expect_response( string $type, array $addons, array $response ): void {
		$short   = VIPGOCI_ADDON_PLUGIN === $type ? 'plugins' : 'themes';
		$headers = array();
		foreach ( $addons as $key => $addon ) {
			$headers[ substr( $key, strlen( $type ) + 1 ) ] = $addon['addon_headers'];
		}
		$body = array( $short => json_encode( array( $short => $headers ) ) );
		if ( VIPGOCI_ADDON_PLUGIN === $type ) {
			$body['all'] = 'true';
		} else {
			$body['translations'] = json_encode( array() );
			$body['locale']       = json_encode( array() );
		}
		HttpApiMock::expect(
			'POST',
			VIPGOCI_WORDPRESS_ORG_API_BASE_URL . '/' . $short . '/update-check/1.1/',
			null,
			json_encode( $response ),
			$body
		);
	}
}
