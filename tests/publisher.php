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

class Publisher extends PHPUnit_Framework_TestCase {

	public function setUp() {
		\OC_User::setUserId('phpunit');
	}

	/**
	 * @dataProvider pushQuotaDataProvider
	 */
	function testPushQuota($notificationExists, $pre, $post) {

		// initialize publisher
		$barriers = array(90, 91.0, 92.0, 93.0, 94.0, 95.0, 96.0, 97.0, 98.0, 99.0, 100.0);
		$this->runQuotaTest($notificationExists, $pre, $post, $barriers);

	}

	/**
	 * @dataProvider pushQuotaDataProvider
	 */
	function testPushQuotaWithMixedBarriers($notificationExists, $pre, $post) {

		// initialize publisher
		$barriers = array(95, 90);
		$this->runQuotaTest($notificationExists, $pre, $post, $barriers);

	}

	/**
	 * @dataProvider pushQuotaDataProvider
	 */
	function testPushQuotaWithoutBarriers($notificationExists, $pre, $post) {

		// initialize publisher
		$barriers = array();
		$this->runQuotaTest(false, $pre, $post, $barriers);

	}

	public function pushQuotaDataProvider() {
		return array(
			array(true, 90, 95),
			array(true, 89.9, 90.1),
			array(false, 89.9, 89.8),
			array(false, 90, 90),
			array(false, 90.0, 90.1),

			array(true, 95, 90),
			array(true, 90.1, 89.9),
			array(false, 89.8, 89.9),
			array(false, 90, 90),
			array(false, 90.1, 90.0),

		);
	}

	public function getStorageInfo($percent) {
		$total = 1.0*1024*1024*1024;
		$used = $total * $percent;
		$free = $total - $used;
		return array('free' => $free, 'used' => $used, 'total' => $total, 'relative' => $percent);
	}

	/**
	 * @param $notificationExists
	 * @param $pre
	 * @param $post
	 * @param $barriers
	 */
	private function runQuotaTest($notificationExists, $pre, $post, $barriers) {
		$publisher = new \OCA\Web_Hooks\Publisher($barriers);
		$receivedNotifications = array();
		$publisher->addNotificationsFunction = function ($topic, $payload) use (&$receivedNotifications) {
			$receivedNotifications[] = array($topic, $payload);
		};

		$pre = $this->getStorageInfo($pre);
		$post = $this->getStorageInfo($post);
		$publisher->pushQuotaChange($pre, $post);

		if ($notificationExists === true) {
			$this->assertEquals(1, count($receivedNotifications));
			$one = $receivedNotifications[0];
			$event = $one[0];
			$payload = $one[1];
			$this->assertTrue(is_array($payload));
			$this->assertArrayHasKey('user', $payload);
			$this->assertArrayHasKey('totalSpace', $payload);
			$this->assertArrayHasKey('usedSpace', $payload);
			$this->assertArrayHasKey('usedPercent', $payload);
		} else {
			$this->assertEquals(0, count($receivedNotifications));
		}
	}

}
