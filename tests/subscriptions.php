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

class Subscriptions extends PHPUnit_Framework_TestCase {

	function testSaveSubscriber() {
		$topic = uniqid('topic-');
		$callback = uniqid('callback-');

		$storage1 = new \OCA\Web_Hooks\Subscriptions();
		$storage1->add($callback, $topic);

		$subscribers = $this->assertSubscriptions($topic, 1);

		$one = $subscribers[0];
		$this->assertTrue(is_array($one));
		$this->assertArrayHasKey('id', $one);
		$this->assertArrayHasKey('topic', $one);
		$this->assertArrayHasKey('callback', $one);

		$already = $storage1->alreadySubscribed($callback, $topic);
		$this->assertTrue($already);
	}

	function testDelete() {
		$topic = uniqid('topic-');
		$callback1 = uniqid('callback-');
		$callback2 = uniqid('callback-');

		$storage = new \OCA\Web_Hooks\Subscriptions();
		$storage->add($callback1, $topic);
		$storage->add($callback2, $topic);
		$this->assertSubscriptions($topic, 2);

		// use second instance to make sure the database is used
		$storage->delete($callback1, $topic);
		$this->assertSubscriptions($topic, 1);

		$storage->delete($callback2, $topic);
		$this->assertSubscriptions($topic, 0);
	}

	function testDeleteById() {
		$topic = uniqid('topic-');
		$callback1 = uniqid('callback-');
		$callback2 = uniqid('callback-');

		$storage = new \OCA\Web_Hooks\Subscriptions();
		$id0 = $storage->add($callback1, $topic);
		$id1 = $storage->add($callback2, $topic);
		$this->assertSubscriptions($topic, 2);

		// use second instance to make sure the database is used
		$storage->deleteById($id0);
		$this->assertSubscriptions($topic, 1);

		$storage->deleteById($id1);
		$this->assertSubscriptions($topic, 0);
	}

	/**
	 * @param string $topic
	 * @param integer $count
	 */
	private function assertSubscriptions($topic, $count) {
		// use second instance to make sure the database is used
		$storage2 = new \OCA\Web_Hooks\Subscriptions();
		$subscribers = $storage2->allByTopic($topic);

		$this->assertEquals($count, count($subscribers));

		return $subscribers;
	}

}
