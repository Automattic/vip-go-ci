<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . '/../../main.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class VipgociRunCleanupPhpcsTest extends TestCase {
	/**
	 * @covers ::vipgoci_run_cleanup_phpcs
	 */
	public function testRunCleanupPhpcs() {
		$tmp_file_name = tempnam(
			sys_get_temp_dir(),
			'vipgoci_' . __FUNCTION__
		);

		$options = array(
			'phpcs-standard-file' => false,
			'phpcs-standard'      => array(
				$tmp_file_name,
			)
		);

		$this->assertTrue(
			file_exists(
				$options['phpcs-standard'][0]
			),
			'Temporary PHPCS standard file does not exist'
		);

		vipgoci_run_cleanup_phpcs(
			$options
		);

		$this->assertTrue(
			file_exists(
				$options['phpcs-standard'][0]
			),
			'Temporary PHPCS standard file does not exist'
		);

		$options['phpcs-standard-file'] = true;

		// Now file in $options['phpcs-standard'][0] should be removed.
		vipgoci_run_cleanup_phpcs(
			$options
		);

		$this->assertFalse(
			file_exists(
				$options['phpcs-standard'][0]
			),
			'Temporary PHPCS standard file exists even though it should have been removed'
		);
	}
}
