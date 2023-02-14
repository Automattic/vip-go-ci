<?php
/**
 * Test vipgoci_options_read_repo_file() function.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

namespace Vipgoci\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Class that implements the testing.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
final class OptionsReadRepositoryConfigFileTest extends TestCase {
	/**
	 * Setup function. Require files.
	 *
	 * @return void
	 */
	protected function setUp() :void {
		require_once __DIR__ . '/helper/OptionsReadRepositoryConfigFile.php';
		require_once __DIR__ . '/../../misc.php';
		require_once __DIR__ . '/../../options.php';
	}

	/**
	 * Tests against the --lint-modified-files-only option.
	 *
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * @return void
	 */
	public function testIfOptionLintOnlyModifiedFilesOptionIsReplacedTest(): void {
		$overritable_mock = $this->getOverritableMock();
		$file_name_mock   = '.vipgoci_options';
		$options          = $this->getOptionsMock();

		vipgoci_options_read_repo_file( $options, $file_name_mock, $overritable_mock );

		$this->assertFalse( $options['lint-modified-files-only'] );

		$this->assertSame(
			array( 'lint-modified-files-only' => false ),
			$options['repo-options-set']
		);
	}

	/**
	 * Get list of options that can be overridden.
	 *
	 * @return array
	 */
	private function getOverritableMock(): array {
		return array(
			'skip-execution'                        => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'skip-draft-prs'                        => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'review-comments-sort'                  => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'review-comments-include-severity'      => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'phpcs'                                 => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'phpcs-severity'                        => array(
				'type'         => 'integer',
				'valid_values' => array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
			),

			'post-generic-pr-support-comments'      => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'phpcs-sniffs-include'                  => array(
				'type'         => 'array',
				'append'       => true,
				'valid_values' => null,
			),

			'phpcs-sniffs-exclude'                  => array(
				'type'         => 'array',
				'append'       => true,
				'valid_values' => null,
			),

			'test-api'                              => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'svg-checks'                            => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'autoapprove'                           => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'autoapprove-php-nonfunctional-changes' => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),

			'lint-modified-files-only'              => array(
				'type'         => 'boolean',
				'valid_values' => array( true, false ),
			),
		);
	}

	/**
	 * Get mocked options.
	 *
	 * @return array
	 */
	private function getOptionsMock(): array {
		return json_decode( '{"repo-owner":"any","repo-name":"any","commit":"any","token":"any","local-git-repo":"any","phpcs-path":"any","phpcs":true,"enforce-https-urls":false,"repo-options":true,"env-options":[],"branches-ignore":[],"phpcs-standard":["WordPress-VIP-Go"],"phpcs-sniffs-include":[],"phpcs-sniffs-exclude":[],"phpcs-runtime-set":[],"phpcs-skip-folders":[],"review-comments-ignore":[],"dismissed-reviews-exclude-reviews-from-team":[],"phpcs-severity":1,"php-path":"php","test-api":false,"svg-checks":false,"svg-scanner-path":"invalid","debug-level":0,"max-exec-time":0,"review-comments-max":10,"review-comments-total-max":200,"skip-large-files-limit":15000,"skip-draft-prs":false,"phpcs-skip-folders-in-repo-options-file":false,"phpcs-skip-scanning-via-labels-allowed":false,"lint":true,"lint-modified-files-only":true,"skip-large-files":true,"lint-skip-folders-in-repo-options-file":false,"dismiss-stale-reviews":false,"dismissed-reviews-repost-comments":true,"review-comments-sort":false,"review-comments-include-severity":false,"phpcs-standard-file":false,"skip-execution":false,"autoapprove":false,"autoapprove-php-nonfunctional-changes":false,"autoapprove-filetypes":[],"post-generic-pr-support-comments":false,"post-generic-pr-support-comments-on-drafts":false,"post-generic-pr-support-comments-string":null,"post-generic-pr-support-comments-skip-if-label-exists":null,"post-generic-pr-support-comments-branches":[],"post-generic-pr-support-comments-repo-meta-match":[],"repo-meta-api-user-id":null,"repo-meta-api-access-token":null,"lint-skip-folders":[],"repo-options-allowed":["skip-execution","skip-draft-prs","review-comments-sort","review-comments-include-severity","phpcs","phpcs-severity","post-generic-pr-support-comments","phpcs-sniffs-include","phpcs-sniffs-exclude","test-api","svg-checks","autoapprove","autoapprove-php-nonfunctional-changes","lint-modified-files-only"],"autoapprove-label":false}', true );
	}
}
