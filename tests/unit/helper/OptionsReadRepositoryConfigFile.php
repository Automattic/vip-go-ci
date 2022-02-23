<?php

declare( strict_types=1 );

/**
 * Mock functions required to execute tests/OptionsReadRepositoryConfigFileTest.php
 */

/**
 * Mock options.php @ vipgoci_gitrepo_fetch_committed_file
 *
 * @param $repo_owner
 * @param $repo_name
 * @param $github_token
 * @param $commit_id
 * @param $file_name
 * @param $local_git_repo
 *
 * @return string
 */
function vipgoci_gitrepo_fetch_committed_file(
	$repo_owner,
	$repo_name,
	$github_token,
	$commit_id,
	$file_name,
	$local_git_repo
): string {
	return "{\"lint-modified-files-only\":false}\n";
}

/**
 * Mock log.php @ vipgoci_log
 *
 * @param string $str         Log message.
 * @param array  $debug_data  Debug data accompanying the log message.
 * @param int    $debug_level Debug level of the message.
 * @param bool   $irc         If to log to IRC.
 *
 * @return void
 */
function vipgoci_log(
        string $str,
        array $debug_data = array(),
        int $debug_level = 0,
        bool $irc = false
) :void {
}
