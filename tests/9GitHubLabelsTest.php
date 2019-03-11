<?php

require_once( __DIR__ . '/IncludesForTests.php' );

use PHPUnit\Framework\TestCase;

final class GitHubLabelsTest extends TestCase {
	const label_name = 'Label for testing';

	var $options_git = array(
		'github-repo-url'	=> null,
		'repo-name'		=> null,
		'repo-owner'		=> null,
	);

	var $options_labels_secrets = array(
		'labels-github-token'	=> null,
		'labels-pr-to-modify'	=> null,
	);

        protected function setUp() {
		vipgoci_unittests_get_config_values(
			'git',
			$this->options_git
		);

		foreach(
			array_keys(
				$this->options_labels_secrets
			) as $option_key
		) {
			$this->options_labels_secrets[ $option_key ] =
				vipgoci_unittests_get_config_value(
					'labels-secrets',
					$option_key,
					true // Fetch from secrets file
				);
		}

		$this->options = array_merge(
			$this->options_git,
			$this->options_labels_secrets
		);

		foreach( array_keys( $this->options ) as $option_key ) {
			if ( null === $this->options[ $option_key ] ) {
				$this->markTestSkipped(
					'Skipping test, not configured correctly (missing option ' . $option_key . ')'
				);

				return;
			}
		}
	
		$this->options['github-token'] =
		$this->options['labels-github-token'];
	}

	protected function tearDown() {
		$this->options_git = null;
		$this->options_labels_secrets = null;
		$this->options = null;
	}

	/**
	 * @covers ::vipgoci_github_label_add_to_pr
	 */
	public function testGitHubAddLabel1() {
		$labels_before = $this->labels_get();

		ob_start();

		vipgoci_github_label_add_to_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::label_name,
			false
		);

		ob_end_clean();

		$labels_after = $this->labels_get();

		$this->assertEquals(
			-1,
			count( $labels_before ) - count( $labels_after )
		);

		$this->assertEquals(
			'Label for testing',
			$labels_after[0]->name
		);
	}

	/**
	 * @covers ::vipgoci_github_label_remove_from_pr
	 */
	public function testGitHubRemoveLabel1() {
		$labels_before = $this->labels_get();

		ob_start();

		vipgoci_github_label_remove_from_pr(
			$this->options['repo-owner'],
			$this->options['repo-name'],
			$this->options['github-token'],
			$this->options['labels-pr-to-modify'],
			$this::label_name,
			false
		);

		ob_end_clean();

		$labels_after = $this->labels_get();

		$this->assertEquals(
			1,
			count( $labels_before ) - count( $labels_after )
		);
	}

	private function labels_get() {
		$github_url =
			VIPGOCI_GITHUB_BASE_URL . '/' .
			'repos/' .
			rawurlencode( $this->options['repo-owner'] ) . '/' .
			rawurlencode( $this->options['repo-name'] ) . '/' .
			'issues/' .
			rawurlencode( $this->options['labels-pr-to-modify'] ) . '/' .
			'labels';

		$data = vipgoci_github_fetch_url(
			$github_url,
			$this->options['github-token']
		);

		$data = json_decode( $data );

		return $data;
	}
}
