<?php
/**
 * Mock functions required to execute tests/OptionsReadRepositoryConfigFileTest.php
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * Mock options.php @ vipgoci_gitrepo_fetch_committed_file
 *
 * @param string $repo_owner     Repository owner.
 * @param string $repo_name      Repository name.
 * @param string $github_token   Token.
 * @param string $commit_id      Commit-ID.
 * @param string $file_name      File name.
 * @param string $local_git_repo Local git repository.
 *
 * @return string
 */
function vipgoci_gitrepo_fetch_committed_file(
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id,
	string $file_name,
	string $local_git_repo
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

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

