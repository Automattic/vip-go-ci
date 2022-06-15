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

/**
 * Loop through plugin/theme directories altered by pull request, determine relevant
 * plugin/theme slugs via the WordPress.org API, and use that look for any
 * plugins/themes that are obsolete or have vulnerabilities via the WPScan
 * API. Save results for further processing.
 *
 * @param array $options                     Options array for the program.
 * @param array $addon_dirs_relevant_to_scan Array of directories which contain plugins/themes altered to be scanned.
 *
 * @return array Associative array with results. For example:
 *   Array(
 *     [plugins/my-plugin] => Array(
 *       [plugin.php] => Array(
 *         [type] => obsolete_but_not_vulnerable
 *         [wpscan_results] => Array(
 *           [friendly_name] => My plugin
 *           [latest_version] => 1.0.0
 *           [last_updated] => 2022-03-15T01:29:00.000Z
 *           [popular] => 1
 *           [vulnerabilities] => Array()
 *         )
 *         [addon_data_for_dir] => Array(
 *           [type] => vipgoci-wpscan-plugin
 *           [addon_headers] => Array(
 *             [Name] => My plugin
 *             [PluginURI] => http://wordpress.org/plugins/my-plugin
 *             [Version] => 1.0.0
 *             [Description] => This is example plugin description.
 *             [Author] => Author Name
 *             [Title] => My plugin
 *             [AuthorName] => Author Name
 *             ...
 *           )
 *           [name] => My plugin
 *           [version_detected] => 0.9
 *           [file_name] => /tmp/my-repo/plugins/my-plugin/plugin.php
 *           [slug] => my-plugin
 *           [new_version] => 1.0.0
 *           [package] => https://downloads.wordpress.org/plugin/my-plugin.1.0.0.zip
 *           [url] => https://wordpress.org/plugins/my-plugin/
 *         )
 *       )
 *     )
 *  )
 */
function vipgoci_wpscan_scan_dirs_altered(
	array $options,
	array $addon_dirs_relevant_to_scan
) :array {
	$problematic_addons_found = array();

	foreach ( $addon_dirs_relevant_to_scan as $addon_dir_relevant ) {
		$addon_data_for_dir = vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
			$options,
			$options['local-git-repo'] . DIRECTORY_SEPARATOR . $addon_dir_relevant
		);

		foreach ( $addon_data_for_dir as $addon_item_key => $addon_item_value ) {
			if ( empty( $addon_item_value['slug'] ) ) {
				continue;
			}

			/*
			 * Begin with collecting statistics on number of lines
			 * and files we scan.
			 */
			vipgoci_stats_per_file(
				$options,
				$addon_dir_relevant . DIRECTORY_SEPARATOR . $addon_item_key,
				'scanned'
			);

			/*
			 * Next, call WPScan API to get security
			 * vulnerability information. Along the way,
			 * we get latest version available.
			 */
			$wpscan_results = vipgoci_wpscan_do_scan_via_api(
				$addon_item_value['slug'],
				$addon_item_value['type'],
				$options['wpscan-api-url'],
				$options['wpscan-api-token']
			);

			// Filter away vulnerabilities that have been fixed in the observed version.
			$wpscan_results = vipgoci_wpscan_filter_fixed_vulnerabilities(
				$addon_item_value['slug'],
				$addon_item_value['version_detected'],
				$wpscan_results
			);

			/*
			 * Find out if addon is obsolete and if it has any
			 * security vulnerabilities.
			 */
			$addon_obsolete = version_compare(
				$wpscan_results[ $addon_item_value['slug'] ]['latest_version'],
				$addon_item_value['version_detected'],
				'>='
			);

			$addon_security_vulnerabilities = ( ! empty(
				$wpscan_results[ $addon_item_value['slug'] ]['vulnerabilities']
			) );

			/*
			 * Process information collected, determine type.
			 */
			if (
				( false === $addon_obsolete ) &&
				( false === $addon_security_vulnerabilities )
			) {
				/*
				 * If plugin is current and has no security
				 * vulnerability, do nothing.
				 */
				continue;
			} elseif (
				( true === $addon_obsolete ) &&
				( false === $addon_security_vulnerabilities )
			) {
				/*
				 * If plugin/theme is obsolete but no
				 * vulnerabilites are noted, add to array
				 * of obsolete but not vulnerable
				 * plugins/themes.
				 */
				$problematic_addons_found[ $addon_dir_relevant ][ $addon_item_key ] = array(
					'type'               => 'obsolete_but_not_vulnerable',
					'wpscan_results'     => $wpscan_results[ $addon_item_value['slug'] ],
					'addon_data_for_dir' => $addon_item_value,
				);
			} elseif ( true === $addon_security_vulnerabilities ) {
				/*
				 * If current or obsolete plugin, and is
				 * vulnerable, then add to vulnerable addons.
				 */
				$problematic_addons_found[ $addon_dir_relevant ][ $addon_item_key ] = array(
					'type'               => 'vulnerable',
					'wpscan_results'     => $wpscan_results[ $addon_item_value['slug'] ],
					'addon_data_for_dir' => $addon_item_value,
				);
			}
		}
	}

	/*
	 * Return information about addons collected.
	 */
	return $problematic_addons_found;
}

