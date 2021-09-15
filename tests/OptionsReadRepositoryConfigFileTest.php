<?php

declare( strict_types=1 );

namespace Vipgoci\Tests\Options;

use PHPUnit\Framework\TestCase;

require_once( __DIR__ . '/options/OptionsReadRepositoryConfigFileMockedFunctions.php' );
require_once( __DIR__ . '/../options.php' );

final class OptionsReadRepositoryConfigFileTest extends TestCase {

	/**
	 * @covers ::vipgoci_options_read_repo_file
	 *
	 * Tests against the --lint-only-modified-files option.
	 */
	public function testIfOptionLintOnlyModifiedFilesOptionIsReplacedTest(): void {
		$overritable_mock = $this->getOverritableMock();
		$file_name_mock = '.vipgoci_options';
		$options = $this->getOptionsMock();

		$this->assertTrue($options['lint-only-modified-files']);

		vipgoci_options_read_repo_file($options, $file_name_mock, $overritable_mock);

		$this->assertFalse($options['lint-only-modified-files']);
	}

	private function getOverritableMock(): array {
		return array(
			'skip-execution'	=> array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'skip-draft-prs'	=> array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'review-comments-sort' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'review-comments-include-severity' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs-severity' => array(
				'type'		=> 'integer',
				'valid_values'	=> array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ),
			),

			'post-generic-pr-support-comments' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'phpcs-sniffs-include' => array(
				'type'		=> 'array',
				'append'	=> true,
				'valid_values'	=> null,
			),

			'phpcs-sniffs-exclude' => array(
				'type'		=> 'array',
				'append'	=> true,
				'valid_values'	=> null,
			),

			'hashes-api' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'svg-checks' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'autoapprove' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'autoapprove-php-nonfunctional-changes' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),

			'lint-only-modified-files' => array(
				'type'		=> 'boolean',
				'valid_values'	=> array( true, false ),
			),
		);
	}

	private function getOptionsMock(): array {
		return json_decode('{"repo-owner":"any","repo-name":"any","commit":"any","token":"any","local-git-repo":"any","phpcs-path":"any","phpcs":true,"enforce-https-urls":false,"repo-options":true,"env-options":[],"branches-ignore":[],"phpcs-standard":["WordPress-VIP-Go"],"phpcs-sniffs-include":[],"phpcs-sniffs-exclude":[],"phpcs-runtime-set":[],"phpcs-skip-folders":[],"review-comments-ignore":[],"dismissed-reviews-exclude-reviews-from-team":[],"phpcs-severity":1,"php-path":"php","hashes-api":false,"svg-checks":false,"svg-scanner-path":"invalid","debug-level":0,"max-exec-time":0,"review-comments-max":10,"review-comments-total-max":200,"skip-large-files-limit":15000,"skip-draft-prs":false,"phpcs-skip-folders-in-repo-options-file":false,"phpcs-skip-scanning-via-labels-allowed":false,"lint":true,"lint-only-modified-files":true,"skip-large-files":true,"lint-skip-folders-in-repo-options-file":false,"dismiss-stale-reviews":false,"dismissed-reviews-repost-comments":true,"review-comments-sort":false,"review-comments-include-severity":false,"phpcs-standard-file":false,"skip-execution":false,"autoapprove":false,"autoapprove-php-nonfunctional-changes":false,"autoapprove-filetypes":[],"post-generic-pr-support-comments":false,"post-generic-pr-support-comments-on-drafts":false,"post-generic-pr-support-comments-string":null,"post-generic-pr-support-comments-skip-if-label-exists":null,"post-generic-pr-support-comments-branches":[],"post-generic-pr-support-comments-repo-meta-match":[],"set-support-level-label":false,"set-support-level-label-prefix":null,"set-support-level-field":null,"repo-meta-api-user-id":null,"repo-meta-api-access-token":null,"lint-skip-folders":[],"repo-options-allowed":["skip-execution","skip-draft-prs","review-comments-sort","review-comments-include-severity","phpcs","phpcs-severity","post-generic-pr-support-comments","phpcs-sniffs-include","phpcs-sniffs-exclude","hashes-api","svg-checks","autoapprove","autoapprove-php-nonfunctional-changes","lint-only-modified-files"],"autoapprove-label":false}', true);
	}
}
