<?php
/**
 * Helper file.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

// phpcs:disable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

/**
 * Helper function for git diffs.
 *
 * @param string $local_git_repo          Local git repo.
 * @param string $repo_owner              Repository owner.
 * @param string $repo_name               Repository name.
 * @param string $github_token            GitHub token.
 * @param string $commit_id_a             Commit-ID A.
 * @param string $commit_id_b             Commit-ID B.
 * @param bool   $renamed_files_also      Renamed files included.
 * @param bool   $removed_files_also      Removed files included.
 * @param bool   $permission_changes_also Permission changes included.
 * @param array  $filter                  Filter to apply.
 *
 * @return array
 */
function vipgoci_git_diffs_fetch(
	string $local_git_repo,
	string $repo_owner,
	string $repo_name,
	string $github_token,
	string $commit_id_a,
	string $commit_id_b,
	bool $renamed_files_also = false,
	bool $removed_files_also = true,
	bool $permission_changes_also = false,
	?array $filter = null
): array {
	return array(
		'files' => array(
			'File1.php'     => 'any',
			'File2.php'     => 'any',
			'src/File3.php' => 'any',
			'src/File4.php' => 'any',
		),
	);
}

// phpcs:enable VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable

