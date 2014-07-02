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

class Hooks {

	const CLASSNAME = 'OCA\Web_Hooks\Hooks';

	private static $preStorageInfo;
	private static $deleteFileInfo = array();
	private static $supportsPostUpdate = false;

	public static function register() {
		$signals = array(
			\OC\Files\Filesystem::signal_post_create,
			\OC\Files\Filesystem::signal_delete,
			'post_delete',
			\OC\Files\Filesystem::signal_post_rename,
			\OC\Files\Filesystem::signal_post_write,
			\OC\Files\Filesystem::signal_write,
		);

		if (defined('\OC\Files\Filesystem::signal_post_update')) {
			self::$supportsPostUpdate = true;
			$signals[] = \OC\Files\Filesystem::signal_post_update;
		}

		foreach ($signals as $signal) {
			\OCP\Util::connectHook(
				\OC\Files\Filesystem::CLASSNAME, $signal,
				self::CLASSNAME, $signal . 'Hook'
			);
		}
	}

	public static function post_createHook($arguments) {
		$h = new Publisher();
		$h->pushFileChange('new', $arguments['path']);
	}

	public static function post_updateHook($arguments) {
		$h = new Publisher();
		$h->pushFileChange('changed', $arguments['path']);
	}

	public static function post_writeHook($arguments) {
		$h = new Publisher();
		if (!self::$supportsPostUpdate) {
			$h->pushFileChange('changed', $arguments['path']);
		}

		// quota change handling
		$postStorageInfo = \OC_Helper::getStorageInfo('/');
		$h->pushQuotaChange(self::$preStorageInfo, $postStorageInfo);
	}

	public static function writeHook() {
		// save storage information
		self::$preStorageInfo = \OC_Helper::getStorageInfo('/');
	}

	public static function deleteHook($arguments) {
		// save delete file info
		$view = \OC\Files\Filesystem::getView();
		if (!is_null($view)) {
			$path = $arguments['path'];
			$info = $view->getFileInfo($path);
			if ($info) {
				self::$deleteFileInfo[$path] = $info;
			}
		}

		// save storage information
		self::$preStorageInfo = \OC_Helper::getStorageInfo('/');
	}

	public static function post_deleteHook($arguments) {
		$h = new Publisher();
		$path = $arguments['path'];
		$info = null;
		if (isset(self::$deleteFileInfo[$path])) {
			$info = self::$deleteFileInfo[$path];
		}
		$h->pushFileChange('deleted', $path, $info);

		// quota change handling
		$postStorageInfo = \OC_Helper::getStorageInfo('/');
		$h->pushQuotaChange(self::$preStorageInfo, $postStorageInfo);
	}

	//post_rename
	public static function post_renameHook($arguments) {
		$h = new Publisher();
		$h->pushFileChange('deleted', $arguments['oldpath']);
		$h->pushFileChange('new', $arguments['newpath']);
	}
}
