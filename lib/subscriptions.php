<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller deepdiver@owncloud.com
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

namespace OCA\Web_Hooks;

class Subscriptions {

	public function add($callback, $topic) {
		$queryParts = array('timestamp', 'topic', 'callback');
		$params = array(time(), $topic, $callback);
		$sql = 'INSERT INTO `*PREFIX*hub_subscriptions`
					(`' . implode('`, `', $queryParts) . '`)
				VALUES (?,?,?)';
		\OC_DB::executeAudited($sql, $params);
		return \OC::$server->getDatabaseConnection()->lastInsertId('*PREFIX*hub_subscriptions');
	}

	public function delete($callback, $topic) {
		$sql = 'DELETE FROM`*PREFIX*hub_subscriptions` WHERE `callback` = ? AND `topic` = ?';
		\OC_DB::executeAudited($sql, array($callback, $topic));
	}

	/**
	 * @param string $topic
	 */
	public function allByTopic($topic) {
		$sql = 'SELECT * FROM `*PREFIX*hub_subscriptions` WHERE `topic` = ?';
		$result = \OC_DB::executeAudited($sql, array($topic));

		return $result->fetchAll();
	}

	public function getById($id) {
		$sql = 'SELECT * FROM `*PREFIX*hub_subscriptions` WHERE `id` = ?';
		$result = \OC_DB::executeAudited($sql, array($id));

		return $result->fetchRow();
	}

	public function deleteById($id) {
		$sql = 'DELETE FROM `*PREFIX*hub_subscriptions` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, array($id));
	}

	public function alreadySubscribed($callback, $topic) {
		$sql = 'SELECT count(*) FROM `*PREFIX*hub_subscriptions` WHERE `callback` = ? AND `topic` = ?';
		$result = \OC_DB::executeAudited($sql, array($callback, $topic));

		return $result->fetchOne() > 0;
	}
}
