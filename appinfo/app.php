<?php

//\OCP\Util::connectHook(
//	'\OC\Files\Cache\Scanner', 'post_scan_file',
//	'OCA\Freenet_Mobile_API\Hooks', 'post_scan_file'
//);
//\OCP\Util::connectHook(
//	\OC\Files\Filesystem::CLASSNAME, \OC\Files\Filesystem::signal_delete,
//	'OCA\Freenet_Mobile_API\Hooks', 'deleteHook'
//);

//
// setup the hooks
//
OCA\Web_Hooks\Hooks::register();

//
// setup back ground jobs
//
\OCP\BackgroundJob::addRegularTask('OCA\Web_Hooks\Cron', 'run');
