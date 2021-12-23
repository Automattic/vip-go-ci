<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once(__DIR__ . './../../misc.php');

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class MiscGitHubEmojisTest extends TestCase
{
    /**
     * @covers ::vipgoci_github_transform_to_emojis
     */
    public function testGitHubEmojis1()
    {
        $this->assertSame(
            '',
            vipgoci_github_transform_to_emojis(
                'exclamation'
            )
        );

        $this->assertSame(
            ':warning:',
            vipgoci_github_transform_to_emojis(
                'warning'
            )
        );
    }
}
