<?php
/**
 * Include all test suite files and
 * vip-go-ci files as well.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

require_once __DIR__ . '/IncludesForTestsDefines.php';
require_once __DIR__ . '/IncludesForTestsOutputControl.php';
require_once __DIR__ . '/IncludesForTestsConfig.php';
require_once __DIR__ . '/IncludesForTestsRepo.php';
require_once __DIR__ . '/IncludesForTestsMisc.php';

if ( 'true' !== getenv( 'VIPGOCI_TESTING_LIVE_API' ) ) {
	$GLOBALS['vipgoci_mock_api_waits'] = true;
	// Submodule objects are already present in the full project checkout.
	// phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv -- Configure only this isolated Git fixture process.
	$config_count = (int) getenv( 'GIT_CONFIG_COUNT' );
	putenv( 'GIT_CONFIG_KEY_' . $config_count . '=url.' . dirname( __DIR__, 2 ) . '.insteadOf' );
	putenv( 'GIT_CONFIG_VALUE_' . $config_count . '=https://github.com/automattic/vip-go-ci.git' );
	putenv( 'GIT_CONFIG_KEY_' . ( $config_count + 1 ) . '=protocol.file.allow' );
	putenv( 'GIT_CONFIG_VALUE_' . ( $config_count + 1 ) . '=always' );
	putenv( 'GIT_CONFIG_COUNT=' . ( $config_count + 2 ) );
	// phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_putenv
	require_once __DIR__ . '/../common/helper/IntegrationApiMock.php';
	require_once __DIR__ . '/../common/helper/CurlApiMock.php';
}
require_once __DIR__ . '/../../requires.php';
