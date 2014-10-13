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

use OC\Files\View;

class Publisher {

	// supported topics below
	const TOPIC_QUOTA = 'owncloud://quota';
	const TOPIC_FS_CHANGE = 'owncloud://filesystem-change';

	public $addNotificationsFunction = null;

	private $barriers;
	private $notifications;
	private $subscriptions;

	/**
	 * @param array $barriers of given quota barriers which are to be used to fire a quota change
	 */
	public function __construct($barriers = null, $view = null) {
		if (is_null($barriers)) {
			$barriers = \OC_Config::getValue('webhook_barriers', array());
		}
		if (!is_array($barriers)) {
			\OC_Log::write('webhooks', "Invalid barriers given: $barriers", \OC_Log::ERROR);
			$barriers = array();
		}
		sort($barriers);

		$this->barriers = $barriers;
		$this->notifications = new Notifications();
		$this->subscriptions = new Subscriptions();

		if (is_null($view)) {
			$view = \OC\Files\Filesystem::getView();
		}
		$this->view = $view;
	}

	/**
	 * Pushes filesystem change events
	 *
	 * @param string $action file change action to be pushed
	 * @param string $path of the file name which has been changed
	 * @param array|null $info
	 */
	public function pushFileChange($action, $path, $info = null) {

		if (substr($path, - strlen('.part')) === '.part') {
			return;
		}

		if (is_null($info)) {
			$info = $this->view->getFileInfo($path);
		}

		$payload = array(
			'action' => $action,
			'path' => $path
		);
		$payload = $this->addUser($payload);
		$payload = $this->addFileInfo($payload, $info);

		$this->addNotifications(self::TOPIC_FS_CHANGE, $payload);

		// handle shared files
		list($owner, $physicalPath, $info) = $this->resolveOwnerPath($path, $info);
		if (!is_null($owner)) {
			$payload = array(
				'action' => $action,
				'path' => $physicalPath
			);
			$payload = $this->addUser($payload, $owner);
			$payload = $this->addFileInfo($payload, $info);

			$this->addNotifications(self::TOPIC_FS_CHANGE, $payload);
		}
	}

	/**
	 * Stores a given notification for all subscribers
	 *
	 * @param $notifications
	 * @param $payload
	 * @param string $topic
	 */
	private function addNotifications($topic, $payload) {
		if (!is_null($this->addNotificationsFunction)) {
			call_user_func($this->addNotificationsFunction, $topic, $payload);
			return;
		}
		$subscriptions = $this->subscriptions->allByTopic($topic);
		foreach($subscriptions as $subscription ) {
			$this->notifications->add($payload, $subscription['id']);
		}
	}

	/**
	 * Pushes quota change event in case given barriers have been breached
	 *
	 * @param array $preStorageInfo storage information before the operation
	 * @param array $postStorageInfo storage information after the operation
	 */
	public function pushQuotaChange($preStorageInfo, $postStorageInfo) {

		$barrierPre = $this->getBarrierIndex($preStorageInfo['relative']);
		$barrierPost = $this->getBarrierIndex($postStorageInfo['relative']);

		if ($barrierPre == $barrierPost) {
			return;
		}

		$payload = array(
			'totalSpace' => $postStorageInfo['total'],
			'usedSpace' => $postStorageInfo['used'],
			'usedPercent' => $postStorageInfo['relative'],
		);

		$payload = $this->addUser($payload);

		$this->addNotifications(self::TOPIC_QUOTA, $payload);
	}

	/**
	 * @param float $relative storage value
	 * @return int index of the barrier the given value has breached
	 */
	private function getBarrierIndex($relative) {
		$index = 0;
		foreach($this->barriers as $barrier) {
			if ($barrier > $relative) {
				break;
			}
			$index++;
		}
		return $index;
	}

	private function addUser($payload, $user = null) {
		if (is_null($user)) {
			$user = \OCP\User::getUser();
		}
		if ($user !== false) {
			$payload['user'] = $user;
		}
		return $payload;
	}

	/**
	 * @param string $path
	 */
	private function addFileInfo($payload, $info) {

		if(isset($info['fileid'])) {
			$payload['fileId'] = $info['fileid'];
		}
		if(isset($info['mimetype'])) {
			$payload['mimeType'] = $info['mimetype'];
		}

		return $payload;
	}

	private function resolveOwnerPath($path, $info) {
		if (!$this->isShared($path)) {
			return array(null, null, null);
		}

		if(isset($info['fileid'])) {
			$fileId = $info['fileid'];
			$owner = $this->view->getOwner($path);
			$ownerView = new View("/$owner/files");
			$path = $ownerView->getPath($fileId);
			return array($owner, $path, $info);
		}

		return array(null, null, null);
	}

	/**
	 * @param string $path
	 * @return bool
	 */
	private function isShared($path) {
		list($storage, ) = $this->view->resolvePath($path);
		/**
		 * @var \OC\Files\Storage\Storage $storage
		 */
		$sid = $storage->getId();
		if (!is_null($sid)) {
			$sid = explode(':', $sid);
			return ($sid[0] === 'shared');
		}

		return false;
	}
}
