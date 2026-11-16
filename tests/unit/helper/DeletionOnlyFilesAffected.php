<?php
/**
 * Fixed file list for testing PR approval decisions without running scanners.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Return a PHP file which is not eligible under the file-type policy.
 *
 * @return array
 */
function vipgoci_github_files_affected_by_commit(): array {
	return array(
		'all' => array( 'code.php' ),
		1     => array( 'code.php' ),
	);
}
