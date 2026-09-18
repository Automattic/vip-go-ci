<?php
/**
 * Verify that latest-release.php behaves as it should.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

namespace Vipgoci\Tests\E2E;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class LatestReleaseTest extends TestCase {
	/**
	 * Temporary file for contents of defines.php.
	 *
	 * @var $temp_file_name
	 */
	private mixed $temp_file_name = '';

	/**
	 * Setup function. Require files, etc.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		$this->temp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci-defines-php-file'
		);
	}

	/**
	 * Clean up.
	 *
	 * @return void
	 */
	protected function tearDown() :void {
		if ( false !== $this->temp_file_name ) {
			unlink( $this->temp_file_name );
		}
	}

	/**
	 * Verify that return value from the script matches
	 * real version number. Also verify that the format
	 * is correct.
	 *
	 * @return void
	 */
	public function testResults(): void {
		// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
		if ( false === $this->temp_file_name ) {
			$this->markTestSkipped(
				'Unable to create temporary file'
			);

			return;
		}

		/*
		 * Resolve the published release independently of latest-release.php.
		 * The moving 'latest' Git tag can lag behind published releases.
		 */
		$ch = curl_init( 'https://api.github.com/repos/Automattic/vip-go-ci/releases/latest' );
		curl_setopt_array(
			$ch,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_CONNECTTIMEOUT => 20,
				CURLOPT_TIMEOUT        => 60,
				CURLOPT_USERAGENT      => 'vip-go-ci-e2e-tests',
				CURLOPT_HTTPHEADER     => array( 'X-GitHub-Api-Version: 2022-11-28' ),
			)
		);
		$release_json = curl_exec( $ch );
		$this->assertNotFalse( $release_json, 'Unable to retrieve the published release.' );
		$this->assertSame( 200, curl_getinfo( $ch, CURLINFO_HTTP_CODE ) );
		$release = json_decode( $release_json, true, 512, JSON_THROW_ON_ERROR );
		$this->assertArrayHasKey( 'tag_name', $release );
		$this->assertMatchesRegularExpression( '/^\d+\.\d+\.\d+$/', $release['tag_name'] );

		/*
		 * Get 'defines.php' from the published release's versioned tag,
		 * put contents of the file into temporary file
		 * and then retrieve the version number
		 * by including the file.
		 */
		exec(
			'git -C . show ' . escapeshellarg( 'refs/tags/' . $release['tag_name'] . ':defines.php' ) .
			' > ' . escapeshellarg( $this->temp_file_name ),
			$git_output,
			$git_status
		);
		$this->assertSame( 0, $git_status, 'Unable to read the release tag; fetch tags before running E2E tests.' );

		require_once $this->temp_file_name;

		$correct_version_number = VIPGOCI_VERSION;
		$this->assertSame( $release['tag_name'], $correct_version_number );

		/*
		 * Run latest-release.php to get latest version number.
		 */
		$returned_version_number = exec(
			escapeshellarg( PHP_BINARY ) . ' latest-release.php',
			$script_output,
			$script_status
		);
		$this->assertSame( 0, $script_status, 'latest-release.php failed.' );

		/*
		 * Verify format of version number is correct.
		 */
		$version_number_preg = '/^(\d+\.)?(\d+\.)?(\*|\d+)$/';

		$this->assertSame(
			1,
			preg_match(
				$version_number_preg,
				$correct_version_number
			)
		);

		$this->assertSame(
			1,
			preg_match(
				$version_number_preg,
				$returned_version_number
			)
		);

		/*
		 * Verify both version numbers match.
		 */
		$this->assertSame(
			$correct_version_number,
			$returned_version_number
		);
		// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.system_calls_exec
	}
}
