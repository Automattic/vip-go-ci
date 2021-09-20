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
	return "{\"lint-only-modified-files\":false}\n";
}
