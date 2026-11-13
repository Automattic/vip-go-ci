<?php
/**
 * Run the fatal HTTP rate-limit scenario with fixture responses.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

define( 'VIPGOCI_UNIT_TESTING', true );
require_once __DIR__ . '/../../../test.bootstrap.php';
require_once __DIR__ . '/../../../requires.php';
$GLOBALS['vipgoci_debug_level'] = 0;
vipgoci_http_api_fetch_url( 'https://transport.fixture.invalid/rate-limit', null );
