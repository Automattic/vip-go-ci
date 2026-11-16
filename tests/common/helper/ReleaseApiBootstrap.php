<?php
/**
 * Prepend the offline transport when executing the release checker in tests.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

require_once __DIR__ . '/IntegrationApiMock.php';
require_once __DIR__ . '/CurlApiMock.php';
