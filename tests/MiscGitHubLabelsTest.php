<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscGitHubLabelsTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_labels
	 */
	public function testGitHubLabel1() {
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
