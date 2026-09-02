<?php
/**
 * Exercise diff discovery in a memory-limited subprocess using a local fixture.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'github-api', 'github-misc' ) as $module ) {
	require_once __DIR__ . '/../../../' . $module . '.php';
}

$GLOBALS['vipgoci_debug_level'] = -1;

$options = array(
	'local-git-repo'  => $argv[1],
	'repo-owner'      => 'fixture',
	'repo-name'       => 'fixture',
	'token'           => 'unused',
	'commit'          => $argv[3],
	'branches-ignore' => array(),
	'skip-draft-prs'  => false,
);

// The GitHub boundary is preloaded; all Git discovery and parsing remain real.
vipgoci_cache(
	array( 'vipgoci_github_prs_implicated', 'fixture', 'fixture', $argv[3], 'unused', array() ),
	array(
		(object) array(
			'number' => 1,
			'base'   => (object) array( 'sha' => $argv[2] ),
		),
	)
);

if ( 'discovery' === $argv[4] ) {
	$skipped = array();
	$result  = vipgoci_github_files_affected_by_commit(
		$options,
		$argv[3],
		$skipped,
		false,
		false,
		false,
		array(
			'file_extensions' => array( 'php' ),
			'skip_folders'    => array(),
		)
	);
	$files   = $result['all'];
	$changes = null;
} else {
	$result  = vipgoci_gitrepo_diffs_fetch_unfiltered( $argv[1], $argv[2], $argv[3] );
	$files   = array_keys( $result['files'] );
	$changes = $result['statistics']['changes'];
}

echo json_encode(
	array(
		'files'   => $files,
		'changes' => $changes,
		'peak'    => memory_get_peak_usage( true ),
	)
);
