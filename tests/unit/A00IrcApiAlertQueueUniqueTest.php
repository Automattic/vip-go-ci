<?php

declare(strict_types=1);

namespace Vipgoci\Tests\Unit;

require_once(__DIR__ . './../../defines.php');
require_once(__DIR__ . './../../other-web-services.php'); // @todo: mock for functions calls
require_once(__DIR__ . './../../statistics.php');

use PHPUnit\Framework\TestCase;

// phpcs:disable PSR1.Files.SideEffects

final class A00IrcApiAlertQueueUniqueTest extends TestCase
{
    /**
     * @covers: vipgoci_irc_api_alert_queue_unique
     */
    public function testIrcQueueUnique1()
    {
        $msg_queue = array(
            'Msg 1',
            'Msg 2',
            'Msg 3'
        );

        $msg_queue_new = vipgoci_irc_api_alert_queue_unique(
            $msg_queue
        );

        $this->assertSame(
            array(
                'Msg 1',
                'Msg 2',
                'Msg 3',
            ),
            $msg_queue_new
        );
    }

    /**
     * @covers: vipgoci_irc_api_alert_queue_unique
     */
    public function testIrcQueueUnique2()
    {
        $msg_queue = array(
            'Msg 1',
            'Msg 2',
            'Msg 3',
            'Msg 3',
            'Msg 3',
            'Msg 3',
        );

        $msg_queue_new = vipgoci_irc_api_alert_queue_unique(
            $msg_queue
        );

        $this->assertSame(
            array(
                'Msg 1',
                'Msg 2',
                '(4x) Msg 3',
            ),
            $msg_queue_new
        );
    }

    /**
     * @covers: vipgoci_irc_api_alert_queue_unique
     */
    public function testIrcQueueUnique3()
    {
        $msg_queue = array(
            'Msg 1',
            'Msg 2',
            'Msg 2',
            'Msg 2',
            'Msg 2',
            'Msg 3',
            'Msg 3',
        );

        $msg_queue_new = vipgoci_irc_api_alert_queue_unique(
            $msg_queue
        );

        $this->assertSame(
            array(
                'Msg 1',
                '(4x) Msg 2',
                '(2x) Msg 3',
            ),
            $msg_queue_new
        );
    }
}
