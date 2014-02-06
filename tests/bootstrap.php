<?php

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = true;

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';

function enableApp($app, $on) {
	try {
		if ($on === true) {
			OC_App::enable($app);
		} else {
			OC_App::disable($app);
		}
	} catch (Exception $e) {
		echo $e;
	}
}


enableApp('web_hooks', false);
OC_Appconfig::deleteKey('web_hooks', "installed_version" );
enableApp('web_hooks', true);

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OC_Log::$enabled = false;
