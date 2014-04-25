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
use OC_L10N_String;
use OCA\Web_Hooks\Subscriptions;

/**
 * Class HubController
 *
 * https://pubsubhubbub.googlecode.com/git/pubsubhubbub-core-0.4.html
 *
 * @package OCA\WebHooks
 */
class HubController {

	/**
	 * @var array
	 */
	private $post;

	/**
	 * @var array
	 */
	private $server;

	/**
	 * @var Subscriptions
	 */
	private $subscriptions;


	public function __construct(array $post = null, array $server = null, $subscriptions = null) {
		if (is_null($post)) {
			$post = $_POST;
		}
		if (is_null($server)) {
			$server = $_SERVER;
		}
		if (is_null($subscriptions)) {
			$subscriptions = new Subscriptions();
		}
		$this->post = $post;
		$this->server = $server;
		$this->subscriptions = $subscriptions;
	}

	/**
	 * 5.1.  Subscriber Sends Subscription Request

	Subscription is initiated by the subscriber making an HTTPS [RFC2616] or HTTP [RFC2616] POST request to the hub URL. This request has a Content-Type of application/x-www-form-urlencoded (described in Section 17.13.4 of [W3C.REC‑html401‑19991224]) and the following parameters in its body:

	hub.callback
	REQUIRED. The subscriber's callback URL where notifications should be delivered. It is considered good practice to use a unique callback URL for each subscription.
	hub.mode
	REQUIRED. The literal string "subscribe" or "unsubscribe", depending on the goal of the request.
	hub.topic
	REQUIRED. The topic URL that the subscriber wishes to subscribe to or unsubscribe from.
	hub.lease_seconds
	OPTIONAL. Number of seconds for which the subscriber would like to have the subscription active. Hubs MAY choose to respect this value or not, depending on their own policies. This parameter MAY be present for unsubscription requests and MUST be ignored by the hub in that case.
	hub.secret
	OPTIONAL. A subscriber-provided secret string that will be used to compute an HMAC digest for authorized content distribution. If not supplied, the HMAC digest will not be present for content distribution requests. This parameter SHOULD only be specified when the request was made over HTTPS [RFC2818]. This parameter MUST be less than 200 bytes in length.

	Subscribers MAY also include additional HTTP [RFC2616] request parameters, as well as HTTP [RFC2616] Headers if they are required by the hub. In the context of social web applications, it is considered good practice to include a From HTTP [RFC2616] header (as described in section 14.22 of Hypertext Transfer Protocol [RFC2616]) to indicate on behalf of which user the subscription is being performed.

	Hubs MUST ignore additional request parameters they do not understand.

	Hubs MUST allow subscribers to re-request subscriptions that are already activated. Each subsequent request to a hub to subscribe or unsubscribe MUST override the previous subscription state for a specific topic URL and callback URL combination once the action is verified. Any failures to confirm the subscription action MUST leave the subscription state unchanged. This is required so subscribers can renew their subscriptions before the lease seconds period is over without any interruption.

	 *
	 * @param $params
	 */
	public function subscribe($params) {
		$callback = $this->getPostParameter('hub.callback', null);
		$mode = $this->getPostParameter('hub.mode', null);
		$topic = $this->getPostParameter('hub.topic', null);

		if (!in_array($mode, array('subscribe', 'unsubscribe'))) {
			$this->respondError(400, "Invalid hub.mode: \"$mode\"");
			return;
		}

		if (!$this->isCallbackValid($callback)) {
			$this->respondError(400, "Invalid hub.callback: \"$callback\"");
			return;
		}

		//
		// TODO: validate topic
		//
		if ($mode === 'subscribe') {
			if (!$this->subscriptions->alreadySubscribed($callback, $topic)) {
				$this->subscriptions->add($callback, $topic);
			}
		} else {
			$this->subscriptions->delete($callback, $topic);
		}

		$this->respond(204, null);
	}

	/**
	 * @param string $key
	 */
	protected function getPostParameter($key, $default = null) {
		$key = str_replace('.', '_', $key);
		return isset($this->post[$key]) ? $this->post[$key] : $default;
	}

	/**
	 * @param integer $statusCode
	 */
	private function respondError($statusCode, $message) {
		$data = array(
			'message' => $message,
		);
		$this->respond($statusCode, $data);
	}

	/**
	 * @param integer $statusCode
	 */
	private function respond($statusCode, $data) {
		header('X-Content-Type-Options: nosniff');
		header('Content-Type: application/json');

		// add status header
		header($this->getStatusMessage($statusCode, '1.1'));

		$this->renderBody($data);
	}


	private function renderBody($data) {
		if (is_null($data)) {
			return;
		}

		// write json to buffer
		if (is_array($data)) {
			array_walk_recursive($data, function(&$value){
				if ($value instanceof OC_L10N_String) {
					$value = (string)$value;
				}
			});
			echo json_encode($data);
		} else {
			echo $data;
		}
	}

	/**
	 * Returns a full HTTP status message for an HTTP status code
	 *
	 * @param int $code
	 * @param string $httpVersion
	 * @return string
	 */
	private function getStatusMessage($code, $httpVersion = '1.1') {

		$msg = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authorative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status', // RFC 4918
			208 => 'Already Reported', // RFC 5842
			226 => 'IM Used', // RFC 3229
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',
			400 => 'Bad request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			418 => 'I\'m a teapot', // RFC 2324
			422 => 'Unprocessable Entity', // RFC 4918
			423 => 'Locked', // RFC 4918
			424 => 'Failed Dependency', // RFC 4918
			426 => 'Upgrade required',
			428 => 'Precondition required', // draft-nottingham-http-new-status
			429 => 'Too Many Requests', // draft-nottingham-http-new-status
			431 => 'Request Header Fields Too Large', // draft-nottingham-http-new-status
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version not supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage', // RFC 4918
			508 => 'Loop Detected', // RFC 5842
			509 => 'Bandwidth Limit Exceeded', // non-standard
			510 => 'Not extended',
			511 => 'Network Authentication Required', // draft-nottingham-http-new-status
		);

		return 'HTTP/' . $httpVersion . ' ' . $code . ' ' . $msg[$code];
	}

	/**
	 * @param string $callback
	 * @return bool
	 */
	private function isCallbackValid($callback) {
		if(is_null($callback)) {
			return false;
		}

		if (strpos($callback, 'http://') === 0) {
			return true;
		}

		if (strpos($callback, 'https://') === 0) {
			return true;
		}

		return false;
	}

}
