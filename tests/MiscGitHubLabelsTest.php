<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class MiscGitHubLabelsTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_labels
	 */
	public function testGitHubLabel1() {
		$this->assertEquals(
			':exclamation:',
			vipgoci_github_labels(
				'warning'
			)
		);
	}
}
