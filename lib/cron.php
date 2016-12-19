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

class Cron {

	/**
	 * Entry point for the back ground worker
	 */
	public static function run() {
		$notifications = new Notifications();
		$subscriptions = new Subscriptions();
		$cron = new Cron($notifications, $subscriptions);
		$cron->pushNotifications();
	}

	/**
	 * @var Notifications
	 */
	private $notifications;

	/**
	 * @var Subscriptions
	 */
	private $subscriptions;

	/**
	 * @var \Closure $pushFunction
	 */
	private $pushFunction;

	/**
	 * @param Notifications $notifications
	 * @param Subscriptions $subscriptions
	 * @param \Closure $pushFunction
	 */
	public function __construct($notifications, $subscriptions, $pushFunction = null) {
		$this->notifications = $notifications;
		$this->subscriptions = $subscriptions;
		$this->pushFunction = $pushFunction;
	}

	public function pushNotifications() {
		$notifications = $this->notifications->all();
		foreach($notifications as $notification) {
			$subscriptionId = $notification['subscription_id'];
			$subscriber = $this->subscriptions->getById($subscriptionId);
			if (is_null($subscriber) || $subscriber === false) {
				$this->log("Unknown subscriber: $subscriptionId");
				$this->subscriptions->deleteById($subscriptionId);
				$this->notifications->deleteById($notification['id']);
				continue;
			}
			$notData = array_merge($notification, $subscriber, array('notificationId' => $notification['id']));
			if ($this->push($notData)) {
				$this->notifications->deleteById($notification['id']);
			} else {
				$notification_json = json_encode($notData);
				$this->log("Pushing notification failed: $notification_json");
			}
		}
	}

	private function push($notification) {

		if (!is_null($this->pushFunction)) {
			return call_user_func($this->pushFunction, $notification);
		}

		$url = $notification['callback'];
		$data = $notification['payload'];
		$topic = $notification['topic'];
		$data_string = $data;
		if (is_array($data)) {
			$data_string = json_encode($data);
		}

		$request = curl_init($url);
		curl_setopt($request, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($request, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($request, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($request, CURLOPT_RETURNTRANSFER, TRUE);

		curl_setopt($request, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($data_string),
			'X-ownCloud-Event: ' . $topic,
		));

		$response = curl_exec($request);
		$code = curl_getinfo($request, CURLINFO_HTTP_CODE);
		curl_close($request);
		if ($code >= 200 && $code < 300) {
			return true;
		}
		log("Push failed to $url: $code - $response");
		return false;
	}

	private function log($message) {
		\OCP\Util::writeLog('webhooks', $message, \OCP\Util::ERROR);
	}
}
