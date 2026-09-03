<?php
/**
 * Exercise production exit-based failure after staging one file.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

foreach ( array( 'defines', 'cache', 'log', 'misc', 'statistics', 'git-repo', 'phpcs-scan' ) as $module ) {
	require __DIR__ . '/../../../' . $module . '.php';
}
$GLOBALS['vipgoci_debug_level'] = 1;
vipgoci_phpcs_scan_batch(
	array(
		'repo-owner'       => 'owner',
		'repo-name'        => 'repo',
		'token'            => 'unused',
		'local-git-repo'   => $argv[1],
		'commit'           => $argv[2],
		'skip-large-files' => false,
	),
	array( 'a.php', 'missing.php' )
);
