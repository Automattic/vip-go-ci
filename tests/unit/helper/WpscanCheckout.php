<?php
/**
 * WordPress metadata boundary for checkout provenance tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

/**
 * Supply benign add-on metadata independently of the checkout mutation.
 *
 * @param mixed ...$arguments Unused WordPress discovery arguments.
 * @return array Fixture metadata.
 */
function vipgoci_wpcore_misc_get_addon_data_and_slugs_for_directory( mixed ...$arguments ): array {
	return $GLOBALS['wpscan_checkout_metadata'];
}

/**
 * The one fixture add-on is affected by the PR.
 *
 * @param mixed ...$arguments Unused matching arguments.
 * @return array Add-ons to exclude.
 */
function vipgoci_wpcore_misc_get_addons_not_altered( mixed ...$arguments ): array {
	return array();
}
