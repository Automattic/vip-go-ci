<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscSanitizePathPrefixTest extends TestCase {
	/**
	 * @covers ::vipgoci_sanitize_path_prefix
	 */
	public function testSanitizePathPrefix1() {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			'a/'
		);

		$this->assertSame(
			'folder1',
			$path
		);
	}

	public function testSanitizePathPrefix2() {
		$path = vipgoci_sanitize_path_prefix(
			'a/folder1',
			'b/'
		);

		$this->assertSame(
			'a/folder1',
			$path
		);
	}
}
