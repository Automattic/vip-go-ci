<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../options.php' );

use PHPUnit\Framework\TestCase;

final class VipgociOptionsIntegerHandleTest extends TestCase {
	/**
	 * @covers ::vipgoci_option_integer_handle
	 */
	public function testOptionsIntegerHandle1() {
		$options = array(
		);

		vipgoci_option_integer_handle(
			$options,
			'mytestoption',
			5
		);

		$this->assertSame(
			array(
				'mytestoption'	=> 5
			),
			$options
		);
	}
}
