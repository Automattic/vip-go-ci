<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/../defines.php' );
require_once( __DIR__ . '/../misc.php' );


final class MiscGitHubLabelsTest extends TestCase {
	/**
	 * @covers ::vipgoci_github_labels
	 */
	public function testGitHubLabel1() {
		$this->assertEquals(
			vipgoci_github_labels(
				'warning'
			),
			':exclamation:'
		);
	}
}
