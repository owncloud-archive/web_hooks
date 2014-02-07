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

class Notifications {

	public function add($payload, $subscriptionId) {
		$payload = json_encode($payload);
		$queryParts = array('timestamp', 'payload', 'subscription_id');
		$params = array(time(), $payload, $subscriptionId);
		$sql = 'INSERT INTO `*PREFIX*hub_notifications`
					(`' . implode('`, `', $queryParts) . '`)
				VALUES (?,?,?)';
		\OC_DB::executeAudited($sql, $params);
	}

	public function deleteById($id) {
		$sql = 'DELETE FROM `*PREFIX*hub_notifications` WHERE `id` = ?';
		\OC_DB::executeAudited($sql, array($id));
	}

	public function deleteAll() {
		$sql = 'DELETE FROM `*PREFIX*hub_notifications`';
		\OC_DB::executeAudited($sql);
	}

	/**
	 * @return array
	 */
	public function all() {
		$sql = 'SELECT * from `*PREFIX*hub_notifications` ORDER BY `timestamp` DESC';
		$result = \OC_DB::executeAudited($sql);

		return $result->fetchAll();
	}
}
