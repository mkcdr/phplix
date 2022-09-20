<?php

use App\Core\Kernel;

if (version_compare(PHP_VERSION, '7.2.9') < 0)
{
	exit('This framework require PHP version 7.2.9 at least.');
}

session_start();

define('FRAMEWORK_VERSION', '0.0.1');
define('START_TIME', microtime(true));

// get framework settings
$settings = require_once __DIR__ . DIRECTORY_SEPARATOR . 'Config' .DIRECTORY_SEPARATOR . 'settings.php';

// get the PSR-4 autoloader
$autoloader = require __DIR__ . DIRECTORY_SEPARATOR . 'autoload.php';

// using the autoload to load application classes
$autoloader('App\\', __DIR__ . DIRECTORY_SEPARATOR);

// set the application environment
switch ($settings['application']['environment']) 
{
	case 'development':
		error_reporting(E_ALL);
		ini_set('display_errors', 1);
		break;

	case 'production':
		ini_set('display_errors', 0);
		error_reporting(0);
		break;
}

// set server timezone
date_default_timezone_set($settings['application']['timezone']);

// set application locale
setlocale(LC_ALL, $settings['application']['locale']);

// remove PHP version header
header_remove('X-Powered-By');

// load common and helper functions
require_once __DIR__ . '/common.php';

// create and run app instance
$app = new Kernel($settings);

// Setting a custom 404 error handler
/* $app->getErrorHandler()->setCustomHandler(404, function($ex) {
	echo render_template('404.html', ['exception' => $ex]);
}); */

$app->run();