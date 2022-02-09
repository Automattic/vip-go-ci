<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../misc.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class MiscSanitizeStringTest extends TestCase {
	/**
	 * @covers ::vipgoci_sanitize_string
	 */
	public function testSanitizeString1() {
		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'FooBar'
			)
		);

		$this->assertSame(
			'foobar',
			vipgoci_sanitize_string(
				'   FooBar   '
			)
		);
	}
}
