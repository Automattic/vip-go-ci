<?php
/**
 * Helper file.
 *
 * @package Automattic/vip-go-ci
 */

declare( strict_types=1 );

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
			'src/File4.php' => 'any'
		),
	);
}
