<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitRepoGitVersionTest extends TestCase {
	/**
	 * Get git version, ensure the returned value
	 * is as expected.
	 *
	 * @covers ::vipgoci_git_version
	 */
	public function testVersion1() {
		vipgoci_unittests_output_suppress();

		$git_version = vipgoci_git_version();

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			0,
			preg_match(
				'/[a-zA-Z, ]/',
				$git_version
			)
		);

		vipgoci_unittests_output_suppress();

		// Verify second call returns same results (should be cached).
		$git_version_2 = vipgoci_git_version();

		vipgoci_unittests_output_unsuppress();

		$this->assertSame(
			$git_version,
			$git_version_2
		);
	}
}
