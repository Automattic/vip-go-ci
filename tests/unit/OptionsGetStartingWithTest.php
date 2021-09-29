<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../options.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class OptionsGetStartingWithTest extends TestCase {
	/**
	 * @covers ::vipgoci_options_get_starting_with
	 */
	public function testOptionsStartingWith() {
		$expected = array(
			'test0' => 't0',
			'test2' => 't9',
		);

		$result = vipgoci_options_get_starting_with(
			array(
				'test1' => 't1',
				'test0' => 't0',
				'test2' => 't9',
				'atest3' => '999',
				'atest4' => '888',
				'atest0' => '777'
			),
			'test',
			array(
				'test1',
			)
		);

		$this->assertsame(
			$expected,
			$result
		);
	}
}
