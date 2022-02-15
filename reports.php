<?php
/**
 * Reporting logic for vip-go-ci.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Generate HTML-style list for the report from an array,
 * allow various options to ease the generation and
 * to make it more generic.
 *
 * @param string $left                 String to the left of each entry.
 * @param string $right                String to the right of each entry.
 * @param array  $items_arr            Array to process.
 * @param string $when_items_arr_empty When array is empty, return this string.
 * @param string $when_key_values      String to use as separator between key and value.
 *
 * @return string HTML for list.
 */
function vipgoci_report_create_scan_details_list(
	string $left,
	string $right,
	array $items_arr,
	string $when_items_arr_empty,
	string $when_key_values = ''
) :string {
	$return_string = '';

	if ( empty( $items_arr ) ) {
		$return_string .= $when_items_arr_empty;
	} else {
		foreach ( $items_arr as $arr_item_key => $arr_item_value ) {
			$return_string .= $left;

			if ( is_numeric( $arr_item_key ) ) {
				$return_string .= vipgoci_output_html_escape( $arr_item_value );
			} else {
				$return_string .= vipgoci_output_html_escape( (string) $arr_item_key );
				$return_string .= $when_key_values;
				$return_string .= vipgoci_output_html_escape( (string) $arr_item_value );
			}

			$return_string .= $right;
		}
	}

	return $return_string;
}

/**
 * Create scan report detail message.
 *
 * Information is either gathered or
 * based on $options and $results.
 *
 * @param array $options Options needed.
 * @param array $results Scanning results.
 *
 * @return string Detail message.
 */
function vipgoci_report_create_scan_details(
	array $options,
	array $results
) :string {
	$details  = '<details>' . PHP_EOL;
	$details .= '<hr />' . PHP_EOL;
	$details .= '<summary>Scan run detail</summary>' . PHP_EOL;

	$details .= '<table>' . PHP_EOL;
	$details .= '<tr>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">';
	$details .= '<h4>Software versions</h4>' . PHP_EOL;

	$details .= '<ul>' . PHP_EOL;

	$details .= '<li>vip-go-ci version: <code>' . vipgoci_output_sanitize_version_number( VIPGOCI_VERSION ) . '</code></li>' . PHP_EOL;

	$php_runtime_version = phpversion();

	if ( ! empty( $php_runtime_version ) ) {
		$details .= '<li>PHP runtime version for vip-go-ci: <code>' . vipgoci_output_sanitize_version_number( $php_runtime_version ) . '</code></li>' . PHP_EOL;
	}

	$php_linting_version = vipgoci_util_php_interpreter_get_version(
		$options['lint-php-path']
	);

	if ( ! empty( $php_linting_version ) ) {
		$details .= '<li>PHP runtime for PHP linting: <code>' . vipgoci_output_sanitize_version_number( $php_linting_version ) . '</code></li>' . PHP_EOL;
	}

	$phpcs_php_version = vipgoci_util_php_interpreter_get_version(
		$options['phpcs-php-path']
	);

	if ( ! empty( $phpcs_php_version ) ) {
		$details .= '<li>PHP runtime for PHPCS: <code>' . vipgoci_output_sanitize_version_number( $phpcs_php_version ) . '</code></li>' . PHP_EOL;
	}

	$phpcs_version = vipgoci_phpcs_get_version(
		$options['phpcs-path'],
		$options['phpcs-php-path']
	);

	if ( ! empty( $phpcs_version ) ) {
		$details .= '<li>PHPCS version: <code>' . vipgoci_output_sanitize_version_number( $phpcs_version ) . '</code></li>' . PHP_EOL;
	}

	$details .= '</ul>' . PHP_EOL;

	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">' . PHP_EOL;

	$details .= '<h4>Options altered</h4>' . PHP_EOL;
	$details .= '<ul>' . PHP_EOL;

	$details .= vipgoci_report_create_scan_details_list(
		'<li><code>',
		'</code></li>',
		$options['repo-options-set'],
		'<li>None</li>',
		'</code> set to <code>'
	);

	$details .= '</ul>' . PHP_EOL;

	$details .= '<h4>Directories not scanned</h4>' . PHP_EOL;

	foreach (
		array(
			'lint-skip-folders'  => 'Not PHP linted',
			'phpcs-skip-folders' => 'Not PHPCS scanned',
		) as $key => $value
	) {
		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<li><code>',
			'</code></li>',
			$options[ $key ],
			'<li>None</li>'
		);

		$details .= '</ul>' . PHP_EOL;
	}

	$details .= '</td>' . PHP_EOL;

	$details .= '<td valign="top" width="33%">' . PHP_EOL;

	$details .= '<h4>PHPCS configuration</h4>' . PHP_EOL;

	foreach (
		array(
			'phpcs-standard'       => 'Standard(s) used',
			'phpcs-sniffs-include' => 'Custom sniffs included',
			'phpcs-sniffs-exclude' => 'Custom sniffs excluded',
		) as $key => $value
	) {
		$details .= '<p>' . vipgoci_output_html_escape( $value ) . ':</p>' . PHP_EOL;
		$details .= '<ul>' . PHP_EOL;

		$details .= vipgoci_report_create_scan_details_list(
			'<li><code>',
			'</code></li>',
			$options[ $key ],
			'<li>None</li>'
		);

		$details .= '</ul>' . PHP_EOL;
	}

	$details .= '</tr>' . PHP_EOL;
	$details .= '</table>' . PHP_EOL;

	$details .= '</details>' . PHP_EOL;

	return $details;
}
