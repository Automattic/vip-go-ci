<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscGitHubEmojisTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_transform_to_emojis
	 */
	public function testGitHubEmojis1() {
		$this->assertEquals(
			'',
			vipgoci_github_transform_to_emojis(
				'exclamation'
			)
		);

		$this->assertEquals(
			':warning:',
			vipgoci_github_transform_to_emojis(
				'warning'
			)
		);
	}
}
