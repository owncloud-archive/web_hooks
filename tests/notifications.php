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

class Notifications extends PHPUnit_Framework_TestCase {

	public function setUp() {
		$storage1 = new \OCA\Web_Hooks\Notifications();
		$storage1->deleteAll();
	}

	function testAddNotifications() {
		$payload = array(
			'user' => 'peter',
			'path' => '/Readme.txt'
		);

		$storage1 = new \OCA\Web_Hooks\Notifications();
		$storage1->add($payload, 1);
		$storage1->add($payload, 2);
		$storage1->add($payload, 3);
		$storage1->add($payload, 4);

		$notifications = $this->assertNotifications(4);

		foreach ($notifications as $one) {
			$this->assertTrue(is_array($one));
			$this->assertArrayHasKey('id', $one);
			$this->assertArrayHasKey('subscription_id', $one);
			$this->assertArrayHasKey('payload', $one);
			$this->assertArrayHasKey('timestamp', $one);
		}
	}

	function testDeleteById() {
		$payload = array(
			'user' => 'peter',
			'path' => '/Readme.txt'
		);

		$storage1 = new \OCA\Web_Hooks\Notifications();
		$storage1->add($payload, 1);
		$storage1->add($payload, 2);
		$storage1->add($payload, 3);
		$storage1->add($payload, 4);

		$notifications = $this->assertNotifications(4);
		$notification = $notifications[0];
		$storage1->deleteById($notification['id']);

		$notifications = $this->assertNotifications(3);
	}

		/**
	 * @param $topic
	 * @param $count
	 */
	private function assertNotifications($count) {
		// use second instance to make sure the database is used
		$storage2 = new \OCA\Web_Hooks\Notifications();
		$subscribers = $storage2->all();

		$this->assertEquals($count, count($subscribers));

		return $subscribers;
	}
}
