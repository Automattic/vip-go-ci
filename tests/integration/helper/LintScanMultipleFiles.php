<?php
/**
 * Helper implementation for LintScanMultipleFilesTest.php.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Placeholder that always returns 'data' string.
 *
 * @param string $repo_owner     Repository owner.
 * @param string $repo_name      Repository name.
 * @param string $token          GitHub token.
 * @param string $commit         Commit-ID.
 * @param string $filename       Name of file.
 * @param string $local_git_repo Local git repository.
 */
function vipgoci_gitrepo_fetch_committed_file(
	string $repo_owner,
	string $repo_name,
	string $token,
	string $commit,
	string $filename,
	string $local_git_repo
) :string {
	return 'data';
}

/**
 * Helper function that does nothing.
 *
 * @param array  $options        Options array for the program.
 * @param array  $prs_implicated Pull requests implicated.
 * @param array  $files_failed   Files that could not be scanned.
 * @param string $msg_start      Start of message.
 * @param string $msg_end        End of message.
 *
 * @return void
 */
function vipgoci_report_submit_scanning_files_failed(
	array $options,
	array $prs_implicated,
	array $files_failed,
	string $msg_start,
	string $msg_end
) :void {
}


