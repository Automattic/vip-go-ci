<?php
/**
 * WordPress Core functionality needed for vip-go-ci.
 *
 * Some of these functions were borrowed and adapted from WordPress and/or b2.
 *
 * WordPress: Copyright 2011-2022 by the contributors of WordPress.
 * b2: Copyright 2001, 2002 Michel Valdrighi - https://cafelog.com
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Retrieve metadata from a file.
 *
 * Searches for metadata in the first 8 KB of a file, such as a plugin or theme.
 * Each piece of metadata must be on its own line. Fields can not span multiple
 * lines, the value will get cut at the end of the first line.
 *
 * If the file data is not within that first 8 KB, then the author should correct
 * their plugin file and move the data headers to the top.
 *
 * Adopted from WordPress: https://core.trac.wordpress.org/browser/tags/6.0/src/wp-includes/functions.php#L6611
 *
 * @link https://codex.wordpress.org/File_Header
 *
 * @param string $file        Absolute path to the file to retrieve metadata from.
 * @param array  $all_headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
 *
 * @return array Array of file header values keyed by header name. For example:
 * Array(
 *   [HeaderKey1] => My value
 *   [HeaderKey2] => My value 2
 * )
 */
function vipgoci_wpcore_misc_get_file_wp_headers(
	string $file,
	array $all_headers
) :array {
	// Pull only the first 8 KB of the file in.
	$file_data = file_get_contents(
		$file,
		false,
		null,
		0,
		8 * VIPGOCI_KB_IN_BYTES
	);

	if ( false === $file_data ) {
		$file_data = '';
	}

	// Make sure we catch CR-only line endings.
	$file_data = str_replace( "\r", "\n", $file_data );

	foreach ( $all_headers as $field => $regex ) {
		if (
			( preg_match(
				'/^(?:[ \t]*<\?php)?[ \t\/*#@]*' . preg_quote( $regex, '/' ) . ':(.*)$/mi',
				$file_data,
				$match
			) )
			&&
			( $match[1] )
		) {
			$all_headers[ $field ] = vipgoci_wpcore_misc_cleanup_header_comment(
				$match[1]
			);
		} else {
			$all_headers[ $field ] = '';
		}
	}

	/*
	 * WordPress runs headers through a translation, we skip
	 * this as we do not have access to the translations.
	 */
	if ( isset( $all_headers['Name'] ) ) {
		$all_headers['Title'] = $all_headers['Name'];
	}

	if ( isset( $all_headers['Author'] ) ) {
		$all_headers['AuthorName'] = $all_headers['Author'];
	}

	return $all_headers;
}

/**
 * Strip close comment and close php tags from file headers used by WordPress.
 *
 * Adopted from https://core.trac.wordpress.org/browser/tags/6.0/src/wp-includes/functions.php#L6544
 *
 * @param string $str Header comment to clean up.
 *
 * @return string String with close comment/php tags removed.
 */
function vipgoci_wpcore_misc_cleanup_header_comment(
	string $str
) :string {
	return trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $str ) );
}

/**
 * Attempts to fetch WordPress theme/plugin headers, such as
 * Name, AuthorName, Version, and so forth from a file.
 * Also attempts to determine if file is part of a theme or a plugin.
 *
 * @param array  $options   Options needed.
 * @param string $file_name Path to file to try to fetch headers from.
 *
 * @return null|array Null on failure to get headers.
 * On success, associative array with type, version, and
 * plugin/theme headers.
 */
function vipgoci_wpcore_misc_get_addon_headers_and_type(
	array $options,
	string $file_name
) :null|array {
	vipgoci_log(
		'Attempting to determine plugin/theme headers for file',
		array(
			'file_name' => $file_name,
		),
		2
	);

	// By default, assume no headers where found.
	$type = null;

	if ( str_ends_with( $file_name, '.php' ) ) {
		/*
		 * Try to retrieve plugin headers.
		 */
		$plugin_data = vipgoci_wpcore_misc_get_file_wp_headers(
			$file_name,
			array(
				'Name'        => 'Plugin Name',
				'PluginURI'   => 'Plugin URI',
				'Version'     => 'Version',
				'Description' => 'Description',
				'Author'      => 'Author',
				'AuthorURI'   => 'Author URI',
				'TextDomain'  => 'Text Domain',
				'DomainPath'  => 'Domain Path',
				'Network'     => 'Network',
				'RequiresWP'  => 'Requires at least',
				'RequiresPHP' => 'Requires PHP',
				'UpdateURI'   => 'Update URI',
			),
		);

		if (
			( ! empty( $plugin_data['Name'] ) ) &&
			( ! empty( $plugin_data['Author'] ) ) &&
			( ! empty( $plugin_data['Version'] ) )
		) {
			$type             = VIPGOCI_WPSCAN_PLUGIN;
			$addon_headers    = $plugin_data;
			$version_detected = $plugin_data['Version'];
		}
	} elseif ( str_ends_with( $file_name, '.css' ) ) {
		/*
		 * If file is CSS, try fetching headers.
		 */
		$theme_data = vipgoci_wpcore_misc_get_file_wp_headers(
			$file_name,
			array(
				'Name'        => 'Theme Name',
				'Version'     => 'Version',
				'Author'      => 'Author',
				'Template'    => 'Template',
				'RequiresWP'  => 'Requires at least',
				'RequiresPHP' => 'Requires PHP',
			),
		);

		if (
			( ! empty( $theme_data['Name'] ) ) &&
			( ! empty( $theme_data['Author'] ) ) &&
			( ! empty( $theme_data['Version'] ) )
		) {
			$type             = VIPGOCI_WPSCAN_THEME;
			$addon_headers    = $theme_data;
			$version_detected = $theme_data['Version'];
		}
	}

	if ( null === $type ) {
		vipgoci_log(
			'Unable to determine plugin/theme headers for file',
			array(
				'file_name' => $file_name,
			),
			2
		);

		return null;
	} else {
		vipgoci_log(
			'Determined plugin/theme headers for file',
			array(
				'file_name'     => $file_name,
				'type'          => $type,
				'addon_headers' => $addon_headers,
			),
			2
		);

		// Some headers were retrieved, return what we collected.
		return array(
			'type'             => $type,
			'addon_headers'    => $addon_headers,
			'name'             => $addon_headers['Name'],
			'version_detected' => $version_detected,
			'file_name'        => $file_name,
		);
	}
}

/**
 * Get list of plugins or themes found in $path, return as array of
 * key-value pairs.
 *
 * This functionality aims for compatibility with get_plugins() in WordPress.
 * The function is adopted from WordPress: https://core.trac.wordpress.org/browser/tags/6.0/src/wp-admin/includes/plugin.php#L254
 *
 * @param array  $options Options array for the program.
 * @param string $path    Path to scan for plugins and themes. Usually this would point a structure similar to wp-content/plugins.
 *
 * @link https://developer.wordpress.org/reference/functions/get_plugins/
 *
 * @return array List of plugins, with array slug as key, value array with details. Example:
 * Array(
 *   [hello/hello.php] => Array(
 *     [type] => vipgoci-wpscan-plugin
 *     [addon_headers] => Array(
 *       [Name] => Hello Dolly
 *       [PluginURI] => http://wordpress.org/plugins/hello-dolly/
 *       [Version] => 1.6
 *       [Description] => This is not just a plugin ...
 *       [Author] => Matt Mullenweg
 *       [AuthorURI] => http://ma.tt/
 *       [Title] => Hello Dolly
 *       [AuthorName] => Matt Mullenweg
 *     )
 *     [name] => Getty Images
 *     [version_detected] => 3.0.5
 *     [filename] => /tmp/plugins/getty-images/getty-images.php
 *   )
 * )
 */
function vipgoci_wpcore_misc_scan_directory_for_addons(
	array $options,
	string $path
): array {
	$plugins_dir  = @opendir( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	$plugin_files = array();

	if ( false === $plugins_dir ) {
		return $plugin_files;
	}

	while ( ( $file = readdir( $plugins_dir ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		if ( '.' === substr( $file, 0, 1 ) ) {
			continue;
		}

		$tmp_subdir = $path . DIRECTORY_SEPARATOR . $file;

		if ( is_dir( $tmp_subdir ) ) {
			$plugins_subdir = @opendir( $tmp_subdir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

			if ( false !== $plugins_subdir ) {
				while ( ( $subfile = readdir( $plugins_subdir ) ) !== false ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
					if ( '.' === substr( $subfile, 0, 1 ) ) {
						continue;
					}

					if ( '.php' === substr( $subfile, -4 ) ) {
						$plugin_files[] = $file . DIRECTORY_SEPARATOR . $subfile;
					}
				}

				closedir( $plugins_subdir );
			}
		} else {
			if ( '.php' === substr( $file, -4 ) ) {
				$plugin_files[] = $file;
			}
		}
	}

	closedir( $plugins_dir );

	$wp_plugins = array();

	if ( empty( $plugin_files ) ) {
		return $wp_plugins;
	}

	foreach ( $plugin_files as $plugin_file ) {
		$tmp_path = $path . DIRECTORY_SEPARATOR . $plugin_file;

		if ( ! is_readable( $tmp_path ) ) {
			continue;
		}

		$plugin_data = vipgoci_wpcore_misc_get_addon_headers_and_type(
			$options,
			$tmp_path
		);

		if ( empty( $plugin_data['addon_headers']['Name'] ) ) {
			continue;
		}

		$wp_plugins[ basename( $plugin_file ) ] = $plugin_data; // @todo: plugin_basename() is used in WordPress.
	}

	return $wp_plugins;
}

/**
 * Attempts to analyze plugin or theme data to determine WordPress.org
 * slugs for them, using the WordPress.org API. Will return array with
 * slugs found along with other information from the API.
 *
 * Parts adopted from WordPress: https://core.trac.wordpress.org/browser/tags/6.0/src/wp-includes/update.php#L257
 *
 * @param array $addons_data Information about plugins/themes. For example:
 * Array(
 *   [hello/hello.php] => Array(
 *     [type] => vipgoci-wpscan-plugin
 *     [addon_headers] => Array(
 *       [Name] => Hello Dolly
 *       [PluginURI] => http://wordpress.org/plugins/hello-dolly/
 *       [Version] => 1.6
 *       [Description] => This is not just a plugin ...
 *       [Author] => Matt Mullenweg
 *       [AuthorURI] => http://ma.tt/
 *       [Title] => Hello Dolly
 *       [AuthorName] => Matt Mullenweg
 *     )
 *     [name] => Getty Images
 *     [version_detected] => 3.0.5
 *     [filename] => /tmp/plugins/getty-images/getty-images.php
 *   )
 * ) // End of array.
 *
 * @return null|array Null on failure. Otherwise returns array of plugins for which WordPress.org
 *                    API gave information about. For example:
 * Array(
 *   [hello/hello.php] => Array( // API returned information.
 *     [id] => w.org/plugins/hello-dolly
 *     [slug] => hello-dolly
 *     [plugin] => hello.php
 *     [new_version] => 1.7.2
 *     [url] => https://wordpress.org/plugins/hello-dolly/
 *     [package] => https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip
 *     ...
 *   ),
 *   [custom-plugin/custom-plugin.php] => null // API returned no information.
 */
function vipgoci_wpcore_api_determine_slug_and_other_for_addons(
	array $addons_data
) :array {
	// Data to send to WordPress.org API.
	$addon_data_to_send = array();

	// Data collected about addons.
	$slugs_by_plugin = array();

	if ( empty( $addons_data ) ) {
		// Got no plugins/themes to query API for, return empty array.
		vipgoci_log(
			'No plugin/themes to query WordPress.org API about, returning empty',
			array(
				'addons_data' => $addons_data,
			),
			0,
			true // Log to IRC.
		);

		return $slugs_by_plugin;
	}

	foreach ( $addons_data as $key => $data_item ) {
		$addon_data_to_send[ $key ] = $data_item['addon_headers'];

		$slugs_by_plugin[ $key ] = null;
	}

	$api_data_raw = vipgoci_http_api_post_url(
		'https://api.wordpress.org/plugins/update-check/1.1/',
		array(
			'plugins' => json_encode(
				array( 'plugins' => $addon_data_to_send )
			),
			'all'     => 'true',
		),
		null, // No access token required.
		false, // HTTP POST.
		false, // Do not JSON encode.
		'application/x-www-form-urlencoded' // Custom HTTP Content-Type.
	);

	if ( is_int( $api_data_raw ) ) {
		vipgoci_log(
			'Unable to get information from WordPress.org API about plugins/themes',
			array(
				'addon_data'   => $addon_data_to_send,
				'api_data_raw' => $api_data_raw,
			),
			0,
			true // Log to IRC.
		);

		return null;
	}

	$api_data = json_decode(
		$api_data_raw,
		true
	);

	if ( ! is_array( $api_data ) ) {
		vipgoci_log(
			'Unable to JSON decode information from WordPress.org API about plugins/themes',
			array(
				'addon_data'   => $addon_data_to_send,
				'api_data_raw' => $api_data_raw,
				'api_data'     => $api_data,
			),
			0,
			true // Log to IRC.
		);

		return null;
	}

	foreach ( $api_data['plugins'] as $key => $data_item ) {
		$slugs_by_plugin[ $key ] = $data_item;
	}

	return $slugs_by_plugin;
}

/**
 * Get header data for plugins or themes in a directory ($path), attempt
 * to determine slugs and fetch other information from WordPress.org
 * API about the plugins/themes, return the information after processing.
 *
 * @param array  $options Options array for the program.
 * @param string $path    Path to directory to analyze.
 *
 * @return array Information about plugins or themes found. Includes
 *               headers found in the plugin/theme, version number of
 *               the plugin/theme, along with information from
 *               WordPress.org API on latest version, download URL, etc.
 *               For example:
 * Array(
 *   [hello/hello.php] => Array(
 *     [type] => vipgoci-wpscan-plugin
 *     [addon_headers] => Array(
 *       [Name] => Hello Dolly
 *       [PluginURI] => http://wordpress.org/plugins/hello-dolly/
 *       [Version] => 1.6
 *       [Description] => This is not just a plugin, ...
 *       [Author] => Matt Mullenweg
 *       [AuthorURI] => http://ma.tt/
 *       [Title] => Hello Dolly
 *       [AuthorName] => Matt Mullenweg
 *       [...]
 *     )
 *   [name] => Hello Dolly
 *   [version_detected] => 1.6
 *   [filename] => /tmp/plugins/hello.php
 *   [slug] => hello-dolly
 *   [new_version] => 1.7.2
 *   [package] => https://downloads.wordpress.org/plugin/hello-dolly.1.7.2.zip
 *   [url] => https://wordpress.org/plugins/hello-dolly/
 * )
 */
function vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory(
	array $options,
	string $path
) :array {
	$plugins_found = vipgoci_wpcore_misc_scan_directory_for_addons(
		$options,
		$path
	);

	$plugin_details = vipgoci_wpcore_api_determine_slug_and_other_for_addons(
		$plugins_found
	);

	if ( null === $plugin_details ) {
		return null;
	}

	/*
	 * Look through plugins found, assign slug found, version numbers, etc.
	 */
	foreach ( $plugins_found as $plugin_key => $plugin_item ) {
		foreach ( array( 'slug', 'new_version', 'package', 'url' ) as $_field_id ) {
			if ( isset( $plugin_details[ $plugin_key ][ $_field_id ] ) ) {
				$plugins_found[ $plugin_key ][ $_field_id ] = $plugin_details[ $plugin_key ][ $_field_id ];
			}
		}
	}

	return $plugins_found;
}

