<?php
/**
 * WPScan API scanning logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Get list of paths to directories containing plugins or themes which were
 * altered by pull requests implicated by current commit and fall
 * within those paths that should be scanned using WPScan API. Paths
 * are relative to base of repository.
 *
 * @param array $options              Options array for the program.
 * @param array $commit_skipped_files Information about skipped files (reference).
 *
 * @return null|array Null when no altered files were identified. Otherwise, array containing paths to specific plugin/theme directories which were altered by any of the pull requests.
 */
function vipgoci_wpscan_find_addon_dirs_altered(
	array $options,
	array &$commit_skipped_files
) :null|array {
	/*
	 * Get list of all files affected by
	 * pull requests implicated by the commit.
	 */
	$files_affected_by_commit_by_pr = vipgoci_github_files_affected_by_commit(
		$options,
		$options['commit'],
		$commit_skipped_files,
		$options['wpscan-api-skip-folders']
	);

	if ( empty( $files_affected_by_commit_by_pr['all'] ) ) {
		vipgoci_log(
			'No plugins/themes found to scan via WPScan API (empty list)',
			array(
				'repo_owner'                          => $options['repo-owner'],
				'repo_name'                           => $options['repo-name'],
				'commit'                              => $options['commit'],
				'files_affected_by_commit_by_pr[all]' => $files_affected_by_commit_by_pr['all'],
			),
			0,
			true // Log to IRC.
		);

		return null;
	}

	/*
	 * Loop through all files affected by the commit,
	 * irrespective of pull request, and try to determine
	 * if an affected file is located in a path that
	 * should be scanned and is thereby eligible
	 * for scan using WPScan API.
	 */

	// First, construct unique list of directories with files affected.
	$directories_changed_by_commit = array();

	foreach ( $files_affected_by_commit_by_pr['all'] as $file_name ) {
		// Directory name where file is located.
		$file_dir = dirname( $file_name );

		vipgoci_array_push_uniquely(
			$directories_changed_by_commit,
			$file_dir
		);
	}

	unset( $file_dir );

	/*
	 * Second, determine directories to scan -- only scan
	 * those that are found in $options['wpscan-api-paths'].
	 */
	$addon_dirs_relevant_to_scan = array();

	foreach (
		$directories_changed_by_commit as $directory_changed_by_commit
	) {
		foreach ( $options['wpscan-api-paths'] as $wpscan_path ) {
			/*
			 * Ensure we collect only base directory of plugins or themes,
			 * not subdirectories.
			 */
			$dir_changed_by_commit_relative = vipgoci_directory_path_get_dir_and_include_base(
				$wpscan_path,
				$directory_changed_by_commit
			);

			if ( empty( $dir_changed_by_commit_relative ) ) {
				continue;
			}

			vipgoci_array_push_uniquely(
				$addon_dirs_relevant_to_scan,
				$dir_changed_by_commit_relative
			);

			break;
		}
	}

	vipgoci_log(
		( empty( $addon_dirs_relevant_to_scan ) ) ?
			'No plugins/themes found to scan via WPScan API' :
			'Found plugins/themes to scan via WPScan API',
		array(
			'repo_owner'                  => $options['repo-owner'],
			'repo_name'                   => $options['repo-name'],
			'wpscan_paths'                => $options['wpscan-api-paths'],
			'addon_dirs_relevant_to_scan' => $addon_dirs_relevant_to_scan,
		),
		0,
		true // Log to IRC.
	);

	if ( empty( $addon_dirs_relevant_to_scan ) ) {
		// No plugins/themes found, do not continue.
		return null;
	}

	return $addon_dirs_relevant_to_scan;
}



