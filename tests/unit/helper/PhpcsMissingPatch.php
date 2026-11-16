<?php
/**
 * Run the production PHPCS path with an unavailable patch and real findings.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc', 'phpcs-scan', 'results', 'reports', 'file-validation', 'output-security', 'other-web-services' ) as $module ) {
	require_once __DIR__ . '/../../../' . $module . '.php';
}
require_once __DIR__ . '/GitHubRequestGuards.php';
$GLOBALS['vipgoci_debug_level'] = -1;
$GLOBALS['vipgoci_test_http_requests'] = array();
$GLOBALS['vipgoci_test_http_responses'] = array(
	'GET /repos/owner/repo/pulls/42/commits' => array( array( 'sha' => $argv[3] ) ),
);
$prs = array( (object) array( 'number' => 42, 'base' => (object) array( 'sha' => $argv[2] ), 'head' => (object) array( 'sha' => $argv[3], 'ref' => 'fixture' ) ) );
vipgoci_cache( array( 'vipgoci_github_prs_implicated', 'owner', 'repo', $argv[3], 'test-token', array() ), $prs );
$files = array();
if ( 'empty' === $argv[4] ) {
	$files['file.php'] = array( 'filename' => 'file.php', 'status' => 'added', 'patch' => '', 'additions' => 2, 'deletions' => 0, 'changes' => 2 );
}
vipgoci_cache(
	array( 'vipgoci_gitrepo_diffs_fetch_unfiltered', $argv[1], $argv[2], $argv[3] ),
	array( 'files' => $files )
);
$options = array(
	'repo-owner' => 'owner', 'repo-name' => 'repo', 'token' => 'test-token',
	'local-git-repo' => $argv[1], 'commit' => $argv[3],
	'branches-ignore' => array(), 'skip-draft-prs' => false,
	'phpcs' => true, 'phpcs-path' => __DIR__ . '/PhpcsScanFixture.php',
	'phpcs-php-path' => PHP_BINARY, 'phpcs-standard' => array(),
	'phpcs-sniffs-exclude' => array(), 'phpcs-severity' => 1, 'phpcs-runtime-set' => array(),
	'phpcs-file-extensions' => array( 'php' ), 'phpcs-skip-folders' => array(),
	'phpcs-skip-scanning-via-labels-allowed' => false,
	'svg-checks' => false, 'svg-file-extensions' => array( 'svg' ),
	'skip-large-files' => false, 'skip-large-files-limit' => 100,
);
$issues = array( 42 => array() );
$stats = array( 42 => array( 'error' => 0, 'warning' => 0, 'info' => 0 ) );
$skipped = array( 42 => array( 'issues' => array(), 'total' => 0 ) );
vipgoci_phpcs_scan_commit( $options, $issues, $stats, $skipped );
exit( 0 );
