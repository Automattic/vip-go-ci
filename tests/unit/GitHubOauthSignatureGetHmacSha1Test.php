<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once( __DIR__ . './../../http-functions.php' );

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class GitHubOauthSignatureGetHmacSha1Test extends TestCase {
	/**
	 * @covers ::vipgoci_oauth1_signature_get_hmac_sha1
	 */
	public function testOAuthHmacSha1() {
		$oauth_method = 'GET';
		$oauth_url = 'https://automattic.com';

		$oauth_keys = array(
			'oauth_consumer_key'	=> '12',
			'oauth_consumer_secret'	=> '34',
			'oauth_token'		=> '56',
			'oauth_token_secret'	=> '78',
		);

		$hmac_sha1 = vipgoci_oauth1_signature_get_hmac_sha1(
			$oauth_method,
			$oauth_url,
			$oauth_keys
		);

		$this->assertSame(
			'wzbKZTPTrm5evZ/0ccfJ03pLTLg=',
			$hmac_sha1
		);
	}
}
