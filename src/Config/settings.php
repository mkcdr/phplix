<?php
/**
 * App Settings
 */

return [
	'application' => [
		// Application environment (development, production)
		'environment' => 'development',

		// Application Timezone
		'timezone' => 'UTC',

		// Application Locale
		'locale' => 'en_US.utf-8',

		// Application Routes
		'routeConfig' => __DIR__ . DIRECTORY_SEPARATOR . 'routes.php',

		// Application Templates Directroy
		'templatesDirectory' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Views',

		// Set custom error handlers
		'errorHandlers' => []
	],
	
	'database' => [
		// Database type (Sqlite, MySql, PostgreSQL and etc.)
		'driver' => 'sqlite',

		// Database path (for sqlite)
		'path' => '',

		// Database host
		'host' => 'localhost',

		// Database port
		'port' => '',

		// Database name
		'name' => '',

		// Database charset
		'charset' => 'utf8mb4',

		// Database username
		'username' => 'root',

		// Database password
		'password' => '',
	]
];