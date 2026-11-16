<?php
/**
 * Memory- and time-bounded entry point for unsafe-file scanner regressions.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'phpcs-scan', 'lint-scan', 'svg-scan' ) as $module ) {
	require_once __DIR__ . '/../../../' . $module . '.php';
}
$GLOBALS['vipgoci_debug_level'] = -1;
$options = array(
	'repo-owner' => 'fixture',
	'repo-name' => 'fixture',
	'token' => 'unused',
	'local-git-repo' => $argv[1],
	'commit' => $argv[2],
	'svg-checks' => array(),
	'skip-large-files' => false,
);
switch ( $argv[4] ) {
	case 'discovery':
		$files = vipgoci_scandir_git_repo( $argv[1] );
		sort( $files );
		echo json_encode( $files );
		break;
	case 'phpcs':
		vipgoci_phpcs_prepare_file( $options, $argv[3] );
		break;
	case 'lint':
		$skipped = array();
		vipgoci_lint_scan_multiple_files( $options, array(), $skipped, array( $argv[3] ) );
		break;
	case 'svg':
		vipgoci_svg_scan_single_file( $options, $argv[3] );
		break;
	default:
		echo json_encode(
			vipgoci_gitrepo_fetch_committed_file( 'fixture', 'fixture', 'unused', $argv[2], $argv[3], $argv[1] )
		);
}
