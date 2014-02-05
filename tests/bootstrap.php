<?php

global $RUNTIME_NOAPPS;
$RUNTIME_NOAPPS = true;

define('PHPUNIT_RUN', 1);

require_once __DIR__.'/../../../lib/base.php';

OC_App::disable('web_hooks');
OC_Appconfig::deleteKey('web_hooks', "installed_version" );
OC_App::enable('web_hooks');

if(!class_exists('PHPUnit_Framework_TestCase')) {
	require_once('PHPUnit/Autoload.php');
}

OC_Hook::clear();
OC_Log::$enabled = false;
