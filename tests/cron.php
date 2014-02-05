<?php

/**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller deepdiver@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Web_Hooks\Tests;

use PHPUnit_Framework_TestCase;

class Cron extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$storage1 = new \OCA\Web_Hooks\Notifications();
		$storage1->deleteAll();
	}


	function testPush() {
		$topic0 = uniqid('topic-');
		$topic1 = uniqid('topic-');
		$callback = uniqid('callback-');
		$payload = array(
			'user' => 'peter',
			'path' => '/Readme.txt'
		);

		$subscriptions = new \OCA\Web_Hooks\Subscriptions();
		$subscriber0 = $subscriptions->add($callback, $topic0);
		$subscriber1 = $subscriptions->add($callback, $topic1);

		$notifications = new \OCA\Web_Hooks\Notifications();
		$notifications->add($payload, $subscriber0);
		$notifications->add($payload, $subscriber1);

		$receivedNotifications = array();
		$cron = new \OCA\Web_Hooks\Cron($notifications, $subscriptions, function($notification) use (&$receivedNotifications) {
			$receivedNotifications[] = $notification;
			return true;
		});
		$cron->pushNotifications();

		$this->assertEquals(2, count($receivedNotifications));

		foreach($receivedNotifications as $one) {
			$this->assertTrue(is_array($one));
			$this->assertArrayHasKey('id', $one);
			$this->assertArrayHasKey('topic', $one);
			$this->assertArrayHasKey('callback', $one);
			$this->assertArrayHasKey('payload', $one);
		}

		$remainingNotifications = $notifications->all();
		$this->assertEquals(0, count($remainingNotifications));
	}
}
