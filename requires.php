<?php
/**
 * Include all code needed.
 *
 * Require each individual file,
 * ensure it is done only once.
 *
 * @package Automattic/vip-go-ci
 */

declare(strict_types=1);

require_once __DIR__ . '/ap-file-types.php';
require_once __DIR__ . '/ap-hashes-api.php';
require_once __DIR__ . '/ap-nonfunctional-changes.php';
require_once __DIR__ . '/ap-svg-files.php';
require_once __DIR__ . '/auto-approval.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/defines.php';
require_once __DIR__ . '/file-validation.php';
require_once __DIR__ . '/github-api.php';
require_once __DIR__ . '/github-misc.php';
require_once __DIR__ . '/git-repo.php';
require_once __DIR__ . '/http-functions.php';
require_once __DIR__ . '/lint-reports.php';
require_once __DIR__ . '/lint-scan.php';
require_once __DIR__ . '/log.php';
require_once __DIR__ . '/main.php';
require_once __DIR__ . '/misc.php';
require_once __DIR__ . '/options.php';
require_once __DIR__ . '/other-utilities.php';
require_once __DIR__ . '/other-web-services.php';
require_once __DIR__ . '/output-security.php';
require_once __DIR__ . '/phpcs-scan.php';
require_once __DIR__ . '/reports.php';
require_once __DIR__ . '/results.php';
require_once __DIR__ . '/skip-file.php';
require_once __DIR__ . '/statistics.php';
require_once __DIR__ . '/support-level-label.php';
require_once __DIR__ . '/svg-scan.php';
require_once __DIR__ . '/wp-core-misc.php';
require_once __DIR__ . '/wpscan-api.php';
require_once __DIR__ . '/wpscan-reports.php';
require_once __DIR__ . '/wpscan-scan.php';

// Require file that executes vipgoci_run().
require_once __DIR__ . '/exec.php';
