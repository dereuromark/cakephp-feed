<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Feed\FeedPlugin;

if (!defined('DS')) {
	define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('WINDOWS')) {
	if (DS === '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('TMP', ROOT . DS . 'tmp' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);
define('APP', ROOT . DS . 'tests' . DS . 'test_app' . DS . 'src' . DS);
define('APP_DIR', 'src');
define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . APP_DIR . DS);

define('WWW_ROOT', ROOT . DS . 'webroot' . DS);
define('CONFIG', __DIR__ . DS . 'config' . DS);
define('TESTS', __DIR__ . DS);

ini_set('intl.default_locale', 'de-DE');

require dirname(__DIR__) . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE_CORE_INCLUDE_PATH . '/src/functions.php';

Configure::write('App', [
	'namespace' => 'App',
	'encoding' => 'UTF-8',
	'fullBaseUrl' => 'http://example.org',
]);
Configure::write('debug', true);

mb_internal_encoding('UTF-8');

date_default_timezone_set('UTC');

$tmpDirs = [
	TMP . 'cache/models',
	TMP . 'cache/persistent',
	TMP . 'cache/views',
];
foreach ($tmpDirs as $tmpDir) {
	if (!is_dir($tmpDir)) {
		mkdir($tmpDir, 0770, true);
	}
}

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_translations_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_translations_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

// Why is this required?
//require ROOT . DS . 'config' . DS . 'bootstrap.php';
//Cake\Routing\Router::defaultRouteClass(DashedRoute::class);

// Why is this needed?
//Cake\Routing\Router::reload();
//require TESTS . 'config' . DS . 'routes.php';

Plugin::getCollection()->add(new FeedPlugin());

// Ensure default test connection is defined
if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

if (WINDOWS) {
	ConnectionManager::setConfig('test', [
		'className' => 'Cake\Database\Connection',
		'driver' => 'Cake\Database\Driver\Mysql',
		'database' => 'cake_test',
		'username' => 'root',
		'password' => '',
		'timezone' => 'UTC',
		'quoteIdentifiers' => true,
		'cacheMetadata' => true,
	]);

	return;
}

ConnectionManager::setConfig('test', [
	'dsn' => getenv('DB_URL'),
	'database' => getenv('db_database'),
	'username' => getenv('db_username'),
	'password' => getenv('db_password'),
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);
