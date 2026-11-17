<?php
/**
 * Exercise status publication with a fake HTTP transport.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../defines.php';
require_once __DIR__ . '/../../../github-api.php';
require_once __DIR__ . '/../../../log.php';

/** Replace only the external POST. */
function vipgoci_http_api_post_url( ...$args ): string|int {
	return 'failure' === $GLOBALS['argv'][1] ? -1 : '{"id":123}';
}

vipgoci_github_status_create( 'fixture', 'repository', 'fixture', str_repeat( 'a', 40 ), 'success', '', 'Complete', 'scan' );
